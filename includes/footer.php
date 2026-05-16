<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$hideChatbotPages = ['login.php', 'register.php', 'forgot-password.php', 'reset-password.php', 'verify-otp.php'];
?>
<?php if (!in_array($currentPage, $hideChatbotPages, true)): ?>
    <section class="chatbot-widget" data-chatbot-widget aria-label="Vehicle consultant">
        <button class="chatbot-toggle" type="button" data-chatbot-toggle>AI Help</button>
        <div class="chatbot-panel" data-chatbot-panel hidden>
            <div class="chatbot-head">
                <div>
                    <strong>AI Vehicle Consultant</strong>
                    <span>Find the best vehicle match</span>
                </div>
                <button type="button" data-chatbot-close aria-label="Close chatbot">&times;</button>
            </div>
            <div class="chatbot-messages" data-chatbot-messages>
                <div class="chat-message bot">Tell me your budget, vehicle type, location, dates, seats, and driver option. I will recommend matching vehicles.</div>
            </div>
            <form class="chatbot-form" data-chatbot-form>
                <label>
                    <span>Budget per day</span>
                    <input type="text" name="budget" inputmode="numeric" placeholder="Enter budget per day" required>
                </label>
                <label>
                    <span>Vehicle type</span>
                    <input type="text" name="vehicle_type" placeholder="Car, bike, SUV" required>
                </label>
                <label>
                    <span>Location</span>
                    <input type="text" name="location" placeholder="Kathmandu" required>
                </label>
                <label>
                    <span>Start date</span>
                    <input type="date" name="start_date" required>
                </label>
                <label>
                    <span>End date</span>
                    <input type="date" name="end_date" required>
                </label>
                <label>
                    <span>Seats</span>
                    <input type="text" name="seats" inputmode="numeric" placeholder="4" required>
                </label>
                <div class="chatbot-driver-field">
                    <span>Driver</span>
                    <div class="chatbot-radio-row" title="Self-drive means you drive yourself. With driver means the company provides a driver.">
                        <label class="chatbot-choice">
                            <input type="radio" name="driver_preference" value="self_drive" checked>
                            <span>Self-drive</span>
                        </label>
                        <label class="chatbot-choice">
                            <input type="radio" name="driver_preference" value="with_driver">
                            <span>With driver</span>
                        </label>
                    </div>
                </div>
                <label class="chatbot-full">
                    <span>Question</span>
                    <input type="text" name="message" placeholder="Example: family trip in Kathmandu">
                </label>
                <button class="btn small chatbot-full" type="submit">Recommend vehicles</button>
            </form>
        </div>
    </section>
<?php endif; ?>
</main>
<footer class="footer">
    <p>Hyrox Rental - Sprint 1</p>
</footer>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
<script>
(function () {
    var widget = document.querySelector('[data-chatbot-widget]');
    if (!widget) {
        return;
    }

    var panel = widget.querySelector('[data-chatbot-panel]');
    var toggle = widget.querySelector('[data-chatbot-toggle]');
    var closeButton = widget.querySelector('[data-chatbot-close]');

    function openChatbot() {
        if (panel) {
            panel.hidden = false;
        }
    }

    function closeChatbot() {
        if (panel) {
            panel.hidden = true;
        }
    }

    if (toggle) {
        toggle.onclick = function (event) {
            event.preventDefault();
            openChatbot();
        };
    }

    if (closeButton) {
        closeButton.onclick = function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeChatbot();
        };
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeChatbot();
        }
    });
})();
</script>
</body>
</html>
