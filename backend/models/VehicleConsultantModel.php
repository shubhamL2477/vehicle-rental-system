<?php
/**
 * Author: Hyrox Rental Team
 * Date: 2026-05-16
 * Purpose: AI vehicle consultant that uses vehicle filters before asking Groq for a short answer.
 */

require_once __DIR__ . '/../../includes/functions.php';

class VehicleConsultantModel
{
    public static function questions()
    {
        return [
            'What is your daily budget?',
            'Which vehicle type do you want?',
            'Which location do you prefer?',
            'What start and end dates do you need?',
            'How many seats do you need?',
            'Do you want self-drive or with driver?',
        ];
    }

    public static function recommend($data)
    {
        $input = self::normalizeInput($data);
        $missingFields = self::missingFields($input);
        [$vehicles, $filters] = filtered_vehicles(self::filterInput($input), 20);
        $recommendations = self::rankVehicles($vehicles, $input);
        $aiAnswer = self::groqAnswer($input, $recommendations, $missingFields);
        $answer = $aiAnswer['answer'] !== '' ? $aiAnswer['answer'] : self::localAnswer($recommendations, $missingFields);

        return [
            'agent' => AI_CONSULTANT_NAME,
            'answer' => $answer,
            'questions' => self::questions(),
            'missing_fields' => $missingFields,
            'filters' => $filters,
            'recommendations' => array_slice($recommendations, 0, 3),
            'ai_provider' => $aiAnswer['used'] ? 'GroqCloud' : 'Local fallback',
            'ai_model' => GROQ_MODEL,
        ];
    }

    private static function normalizeInput($data)
    {
        if (isset($data['preferences']) && is_array($data['preferences'])) {
            $data = array_merge($data['preferences'], $data);
            unset($data['preferences']);
        }

        $driverPreference = strtolower(trim((string) ($data['driver_preference'] ?? '')));
        $driverPreference = str_replace('-', '_', $driverPreference);

        if (in_array($driverPreference, ['driver', 'with_driver', 'with driver', 'yes', '1'], true)) {
            $driverPreference = 'with_driver';
        } elseif (in_array($driverPreference, ['self', 'self_drive', 'self drive', 'no', '0'], true)) {
            $driverPreference = 'self_drive';
        } else {
            $driverPreference = '';
        }

        return [
            'message' => trim((string) ($data['message'] ?? '')),
            'budget' => max(0, (float) ($data['budget'] ?? $data['max_price'] ?? 0)),
            'vehicle_type' => trim((string) ($data['vehicle_type'] ?? $data['type'] ?? '')),
            'location' => trim((string) ($data['location'] ?? '')),
            'start_date' => self::validDate($data['start_date'] ?? ''),
            'end_date' => self::validDate($data['end_date'] ?? ''),
            'seats' => max(0, (int) ($data['seats'] ?? 0)),
            'driver_preference' => $driverPreference,
        ];
    }

    private static function validDate($date)
    {
        $date = trim((string) $date);
        $time = strtotime($date);

        if ($date === '' || $time === false) {
            return '';
        }

        return date('Y-m-d', $time);
    }

    private static function missingFields($input)
    {
        $missing = [];

        if ($input['budget'] <= 0) {
            $missing[] = 'budget';
        }

        if ($input['vehicle_type'] === '') {
            $missing[] = 'vehicle_type';
        }

        if ($input['location'] === '') {
            $missing[] = 'location';
        }

        if ($input['start_date'] === '' || $input['end_date'] === '' || $input['end_date'] < $input['start_date']) {
            $missing[] = 'dates';
        }

        if ($input['seats'] < 1) {
            $missing[] = 'seats';
        }

        if ($input['driver_preference'] === '') {
            $missing[] = 'driver_preference';
        }

        return $missing;
    }

    private static function filterInput($input)
    {
        return [
            'budget' => $input['budget'],
            'max_price' => $input['budget'],
            'vehicle_type' => $input['vehicle_type'],
            'location' => $input['location'],
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'seats' => $input['seats'],
            'driver_preference' => $input['driver_preference'],
        ];
    }

    private static function rankVehicles($vehicles, $input)
    {
        $ranked = [];

        foreach ($vehicles as $vehicle) {
            $dailyPrice = self::dailyPrice($vehicle, $input['driver_preference']);
            $seatCount = (int) ($vehicle['seating_capacity'] ?? 0);
            $score = 0;
            $reasons = [];

            if ($input['budget'] > 0 && $dailyPrice > 0 && $dailyPrice <= $input['budget']) {
                $score += 35;
                $reasons[] = 'fits your ' . money($input['budget']) . ' daily budget';
            }

            if ($input['location'] !== '' && stripos((string) $vehicle['location'], $input['location']) !== false) {
                $score += 20;
                $reasons[] = 'available in ' . $vehicle['location'];
            }

            if ($input['vehicle_type'] !== '' && self::vehicleTypeMatches($vehicle, $input['vehicle_type'])) {
                $score += 20;
                $reasons[] = 'matches the requested ' . $input['vehicle_type'] . ' type';
            }

            if ($input['seats'] > 0 && $seatCount >= $input['seats']) {
                $score += 15;
                $reasons[] = 'has ' . $seatCount . ' seat(s) for your group';
            }

            if ($input['start_date'] !== '' && $input['end_date'] !== '') {
                $score += 10;
                $reasons[] = 'is free for ' . $input['start_date'] . ' to ' . $input['end_date'];
            }

            if ((int) ($vehicle['review_count'] ?? 0) > 0) {
                $score += (float) ($vehicle['average_rating'] ?? 0);
                $reasons[] = rating_text($vehicle['average_rating'], $vehicle['review_count']);
            }

            if (!$reasons) {
                $reasons[] = 'matches the available vehicle filters';
            }

            $ranked[] = [
                'score' => $score,
                'vehicle_id' => (int) $vehicle['id'],
                'name' => (string) $vehicle['name'],
                'company_name' => (string) $vehicle['company_name'],
                'location' => (string) $vehicle['location'],
                'category_name' => (string) $vehicle['category_name'],
                'type_name' => (string) $vehicle['type_name'],
                'seating_capacity' => $seatCount,
                'daily_price' => $dailyPrice,
                'self_drive_price' => (float) $vehicle['self_drive_price'],
                'with_driver_price' => (float) $vehicle['with_driver_price'],
                'driver_preference' => $input['driver_preference'] ?: 'self_drive',
                'rating' => (float) ($vehicle['average_rating'] ?? 0),
                'review_count' => (int) ($vehicle['review_count'] ?? 0),
                'image' => vehicle_image_src($vehicle['image'] ?? ''),
                'url' => 'vehicle.php?id=' . (int) $vehicle['id'],
                'explanation' => ucfirst(implode(', ', $reasons)) . '.',
            ];
        }

        usort($ranked, function ($left, $right) {
            if ($left['score'] === $right['score']) {
                return $left['daily_price'] <=> $right['daily_price'];
            }

            return $right['score'] <=> $left['score'];
        });

        return $ranked;
    }

    private static function dailyPrice($vehicle, $driverPreference)
    {
        if ($driverPreference === 'with_driver') {
            return (float) $vehicle['with_driver_price'];
        }

        return (float) $vehicle['self_drive_price'];
    }

    private static function vehicleTypeMatches($vehicle, $type)
    {
        $type = strtolower($type);

        return strpos(strtolower((string) $vehicle['type_name']), $type) !== false
            || strpos(strtolower((string) $vehicle['category_name']), $type) !== false
            || strpos(strtolower((string) $vehicle['name']), $type) !== false;
    }

    private static function localAnswer($recommendations, $missingFields)
    {
        if ($missingFields) {
            return 'Please share your ' . implode(', ', $missingFields) . ' so I can recommend better vehicles.';
        }

        if (!$recommendations) {
            return 'I could not find an available vehicle for those details. Try a higher budget, nearby location, or different dates.';
        }

        $top = $recommendations[0];
        return 'My top match is ' . $top['name'] . ' because it ' . strtolower(rtrim($top['explanation'], '.')) . '.';
    }

    private static function groqAnswer($input, $recommendations, $missingFields)
    {
        if (GROQ_API_KEY === '' || !function_exists('curl_init')) {
            return ['used' => false, 'answer' => ''];
        }

        $safeRecommendations = array_map(function ($item) {
            return [
                'name' => $item['name'],
                'location' => $item['location'],
                'type' => $item['type_name'],
                'seats' => $item['seating_capacity'],
                'daily_price' => $item['daily_price'],
                'explanation' => $item['explanation'],
            ];
        }, array_slice($recommendations, 0, 3));

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are ' . AI_CONSULTANT_NAME . ', a vehicle rental assistant. Ask for missing budget, vehicle type, location, dates, seats, or driver preference. Driver preference means self-drive or with driver, not manual or automatic transmission. Recommend only the vehicles provided by the app. Keep the answer under 90 words.',
            ],
            [
                'role' => 'user',
                'content' => json_encode([
                    'customer_message' => $input['message'],
                    'preferences' => $input,
                    'missing_fields' => $missingFields,
                    'top_vehicle_matches' => $safeRecommendations,
                ]),
            ],
        ];

        $payload = [
            'model' => GROQ_MODEL,
            'messages' => $messages,
            'temperature' => 0.3,
            'max_completion_tokens' => 220,
        ];

        $curl = curl_init(GROQ_API_URL);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . GROQ_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            db_log_error(new RuntimeException('Groq API request failed: HTTP ' . $status . ' ' . $error), 'VehicleConsultantModel::groqAnswer');
            return ['used' => false, 'answer' => ''];
        }

        $json = json_decode($response, true);
        $answer = trim((string) ($json['choices'][0]['message']['content'] ?? ''));

        return [
            'used' => $answer !== '',
            'answer' => $answer,
        ];
    }
}
