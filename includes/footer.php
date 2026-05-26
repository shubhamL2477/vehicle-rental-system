    <section class="chatbot-widget" data-chatbot-widget aria-label="Vehicle consultant">
        <button class="chatbot-toggle" type="button" data-chatbot-toggle>AI Help</button>
        <div class="chatbot-panel" data-chatbot-panel hidden>
            <div class="chatbot-head">
                <div>
                    <strong>AI Vehicle Consultant</strong>
                    <span>Budget, type, location, dates, seats</span>
                </div>
                <button type="button" data-chatbot-close aria-label="Close chatbot">&times;</button>
            </div>
            <div class="chatbot-messages" data-chatbot-messages>
                <div class="chat-message bot">Hi! I can check live availability first, then recommend vehicles by budget, type, location, dates, seats, and driver preference.</div>
            </div>
            <form class="chatbot-form" data-chatbot-form>
                <label>
                    <span>Budget per day</span>
                    <input type="number" name="budget" min="0" step="100" placeholder="Rs.">
                </label>
                <label>
                    <span>Vehicle type</span>
                    <input type="text" name="vehicle_type" placeholder="Car, bike, SUV">
                </label>
                <label>
                    <span>Location</span>
                    <input type="text" name="location" placeholder="Kathmandu">
                </label>
                <label>
                    <span>Start date</span>
                    <input type="date" name="start_date">
                </label>
                <label>
                    <span>End date</span>
                    <input type="date" name="end_date">
                </label>
                <label>
                    <span>Seats</span>
                    <input type="number" name="seats" min="1" placeholder="4">
                </label>
                <label>
                    <span>Driver</span>
                    <select name="driver_preference">
                        <option value="self_drive">Self-drive</option>
                        <option value="with_driver">With driver</option>
                    </select>
                </label>
                <label class="chatbot-full">
                    <span>Question</span>
                    <input type="text" name="message" placeholder="Ask about booking, payment, account...">
                </label>
                <button class="btn small chatbot-full" type="submit">Recommend vehicles</button>
            </form>
        </div>
    </section>
</main>
<footer class="footer">
    <p>&copy; <?= date('Y') ?> Hyrox Rental. All rights reserved.</p>
    <p>
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <?php if (current_user()): ?>
            <a href="dashboard.php">Dashboard</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </p>
</footer>
<?php
$scriptFiles = [
    'assets/js/auth.js',
    'assets/js/booking.js',
    'assets/js/gps.js',
    'assets/js/vehicle-filters.js',
    'assets/js/vehicle-search.js',
    'assets/js/chatbot.js',
];
?>
<?php foreach ($scriptFiles as $scriptFile): ?>
    <?php $scriptPath = __DIR__ . '/../' . $scriptFile; ?>
    <?php if (is_file($scriptPath)): ?>
        <script src="<?= e($scriptFile) ?>?v=<?= e((string) filemtime($scriptPath)) ?>"></script>
    <?php endif; ?>
<?php endforeach; ?>
</body>
</html>
