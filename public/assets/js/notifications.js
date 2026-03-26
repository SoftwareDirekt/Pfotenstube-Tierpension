function loadNotifications() {
    $.ajax({
        url: '/admin/notifications',
        method: 'GET',
        success: function (notifications) {
            const container = $('#notifications-container');
            container.empty();

            if (notifications.length === 0) {
                container.html(`
                    <li class="dropdown-item text-center p-3">
                        <small class="text-muted">Keine neuen Benachrichtigungen</small>
                    </li>
                `);
                $('#notification-dot').addClass('d-none');
                $('#notification_bell').removeClass('vibrate');
                return;
            }

            // Render notifications
            notifications.forEach(notification => {
                const isRead = notification.read;
                const bgClass = isRead ? '' : 'bg-light';

                let iconClass = 'mdi-bell-outline text-primary';
                if (notification.type === 'vaccination_alert') {
                    iconClass = 'mdi-needle text-danger';
                } else if (notification.type === 'new_reservation') {
                    iconClass = 'mdi-calendar-clock text-warning';
                } else if (notification.type === 'reservation_cancelled') {
                    iconClass = 'mdi-calendar-remove text-danger';
                }

                container.append(`
                    <div class="notification-item notification-alert p-3 border-bottom ${bgClass}"
                         data-id="${notification.id}"
                         data-type="${notification.type}"
                         data-dog-id="${notification.dog_id}"
                         style="cursor: pointer;">
                         <div class="d-flex align-items-start">
                            <i class="mdi ${iconClass} me-3 fs-4"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-1">${notification.title}</div>
                                <div class="text-muted small">${notification.message}</div>
                            </div>
                        </div>
                    </div>
                `);
            });

            // Show notification dot and bell animation only for unread notifications
            const unreadNotifications = notifications.filter(n => !n.read);
            if (unreadNotifications.length > 0) {
                $('#notification-dot').removeClass('d-none');
                $('#notification_bell').addClass('vibrate');
            } else {
                $('#notification-dot').addClass('d-none');
                $('#notification_bell').removeClass('vibrate');
            }

            // Add click handler for notifications
            $('.notification-item').on('click', function () {
                const id = $(this).data('id');
                const type = $(this).data('type');
                const dogId = $(this).data('dog-id');

                // Mark as read when clicked
                markAsRead(id, $(this));

                if (type === 'new_reservation') {
                    const url = $('meta[name="homepage-pending-url"]').attr('content');
                    if (url) {
                        window.location.href = url;
                        return;
                    }
                }

                if (dogId && typeof dogInfo === 'function') {
                    dogInfo(dogId);
                }
            });
        }
    });
}

function markAsRead(notificationId, $item) {
    $.ajax({
        url: '/admin/notifications/mark-as-read',
        method: 'POST',
        data: {
            notification_id: notificationId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function () {
            // Remove background highlight for vaccination alerts
            $item.removeClass('bg-light');
            // Check if any unread notifications remain
            if ($('.notification-item.bg-light').length === 0) {
                $('#notification-dot').addClass('d-none');
                $('#notification_bell').removeClass('vibrate');
            }
        }
    });
}

function markAllAsRead() {
    $.ajax({
        url: '/admin/notifications/mark-all-as-read',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function () {
            // Remove background highlight from all notifications
            $('.notification-item').removeClass('bg-light');
            $('#notification-dot').addClass('d-none');
            $('#notification_bell').removeClass('vibrate');
        }
    });
}

$(document).on('click', '#mark-all-read-link', function () {
    markAllAsRead();
});

$(document).ready(function () {
    loadNotifications();
    setInterval(loadNotifications, 60000);

    // Header hide/show on scroll
    let lastScrollTop = 0;
    const navbar = $('#layout-navbar');

    $(window).scroll(function () {
        const scrollTop = $(this).scrollTop();

        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down - hide header
            navbar.addClass('hidden');
        } else {
            // Scrolling up - show header
            navbar.removeClass('hidden');
        }

        lastScrollTop = scrollTop;
    });
});
