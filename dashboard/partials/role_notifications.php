    <?php $roleNotifications = NotificationService::latestForUser((int) $me['id'], 12); ?>
    <section class="box dashboard-panel">
        <div class="panel-title-row">
            <div>
                <h2>Notifications</h2>
                <p class="muted">Recent booking and system updates for your role.</p>
            </div>
            <form action="actions/notification.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button class="btn light small" type="submit">Mark all read</button>
            </form>
        </div>
        <div class="notification-list">
            <?php foreach ($roleNotifications as $notice): ?>
                <article class="notification-card <?= $notice['read_at'] ? 'read' : 'unread' ?>">
                    <div class="card-line">
                        <strong><?= e($notice['title']) ?></strong>
                        <span class="badge <?= $notice['read_at'] ? 'good' : 'wait' ?>"><?= $notice['read_at'] ? 'read' : 'new' ?></span>
                    </div>
                    <p><?= e($notice['message']) ?></p>
                    <small class="muted"><?= e($notice['created_at']) ?> Â| <?= e($notice['type']) ?></small>
                    <?php if (!$notice['read_at']): ?>
                        <form action="actions/notification.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notification_id" value="<?= e($notice['id']) ?>">
                            <button class="btn light tiny" type="submit">Mark read</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$roleNotifications): ?>
            <div class="empty-state"><h3>No notifications</h3><p>Updates for your account will appear here.</p></div>
        <?php endif; ?>
    </section>


