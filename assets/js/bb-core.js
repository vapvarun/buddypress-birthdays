/*-----------------------------
* BuddyPress Birthdays Widget JS
* Professional Production Version
* Enhanced User Experience & Performance
-----------------------------*/

(function($) {
    "use strict";

    // Enhanced birthday widget functionality
    var BPBirthdays = {
        settings: {
            tooltipDelay: 300,
            debounceDelay: 250,
            fadeSpeed: 200,
            animationDuration: 300
        },

        cache: {},

        init: function() {
            this.cacheElements();
            this.initTooltips();
            this.initAccessibility();
            this.bindEvents();
            this.optimizeLayout();
            this.initSpecialEffects();
        },

        cacheElements: function() {
            this.cache.$document = $(document);
            this.cache.$window = $(window);
            this.cache.$body = $('body');
            this.cache.$birthdayWidgets = $('.widget_bp_birthdays');
            this.cache.$birthdayLists = $('.bp-birthday-users-list');
            this.cache.$sendWishesButtons = $('.send_wishes');
            this.cache.$todayBirthdays = $('.today-birthday');
        },

        bindEvents: function() {
            // Use event delegation for better performance
            this.cache.$document.on('click.bpBirthdays', '.send_wishes', this.handleWishesClick.bind(this));
            
            // Handle widget updates
            this.cache.$document.on('widget-updated.bpBirthdays', this.handleWidgetUpdate.bind(this));
            
            // Handle responsive behavior
            this.cache.$window.on('resize.bpBirthdays', this.debounce(this.handleResize.bind(this), this.settings.debounceDelay));
            
            // Handle visibility changes for performance
            this.cache.$document.on('visibilitychange.bpBirthdays', this.handleVisibilityChange.bind(this));
            
            // Handle scroll events for animations
            this.cache.$window.on('scroll.bpBirthdays', this.debounce(this.handleScroll.bind(this), 100));
        },

        handleWishesClick: function(e) {
            const $button = $(e.currentTarget);
            const href = $button.attr('href');
            
            if (!href || href === '#') {
                e.preventDefault();
                this.showMessage('Unable to send wishes at this time.', 'error');
                return;
            }

            // Add loading state
            $button.addClass('loading').attr('aria-disabled', 'true');
            
            // Optional: Track analytics
            this.trackWishEvent($button);
            
            // Provide user feedback
            this.showMessage('Redirecting to compose message...', 'info');
        },

        initTooltips: function() {
            if (this.cache.$sendWishesButtons.length === 0) return;

            // Enhanced tooltip behavior with better timing
            this.cache.$sendWishesButtons.each((index, element) => {
                const $button = $(element);
                const $tooltip = $button.find('.tooltip_wishes');
                
                if ($tooltip.length === 0) return;

                let tooltipTimer;

                $button
                    .on('mouseenter.tooltip', () => {
                        clearTimeout(tooltipTimer);
                        tooltipTimer = setTimeout(() => {
                            $tooltip.addClass('visible');
                            this.positionTooltip($button, $tooltip);
                        }, this.settings.tooltipDelay);
                    })
                    .on('mouseleave.tooltip', () => {
                        clearTimeout(tooltipTimer);
                        $tooltip.removeClass('visible');
                    })
                    .on('focus.tooltip', () => {
                        $tooltip.addClass('visible');
                        this.positionTooltip($button, $tooltip);
                    })
                    .on('blur.tooltip', () => {
                        $tooltip.removeClass('visible');
                    });
            });
        },

        positionTooltip: function($button, $tooltip) {
            // Smart tooltip positioning to avoid viewport edges
            const buttonOffset = $button.offset();
            const tooltipWidth = $tooltip.outerWidth();
            const viewportWidth = $(window).width();
            
            if (buttonOffset.left + tooltipWidth > viewportWidth) {
                $tooltip.addClass('tooltip-right');
            } else {
                $tooltip.removeClass('tooltip-right');
            }
        },

        initAccessibility: function() {
            // Add ARIA labels for better accessibility
            this.cache.$sendWishesButtons.each(function() {
                const $button = $(this);
                if (!$button.attr('aria-label')) {
                    $button.attr('aria-label', 'Send birthday wishes');
                }
                $button.attr('role', 'button');
            });

            // Add role attributes where needed
            this.cache.$birthdayLists.attr('role', 'list');
            this.cache.$birthdayLists.find('li').attr('role', 'listitem');
            
            // Add landmark roles
            this.cache.$birthdayWidgets.attr('role', 'complementary').attr('aria-label', 'Birthday notifications');
        },

        initSpecialEffects: function() {
            // Initialize special effects for today's birthdays
            if (this.cache.$todayBirthdays.length > 0) {
                this.initBirthdayAnimations();
                this.initConfettiEffect();
            }
            
            // Initialize intersection observer for scroll animations
            this.initScrollAnimations();
        },

        initBirthdayAnimations: function() {
            // Add special animations for today's birthdays
            this.cache.$todayBirthdays.each(function(index) {
                const $item = $(this);
                setTimeout(() => {
                    $item.addClass('birthday-celebrate');
                }, index * 200);
            });
        },

        initConfettiEffect: function() {
            // Simple confetti effect for today's birthdays (optional)
            if (typeof this.createConfetti === 'function') {
                this.cache.$todayBirthdays.each((index, element) => {
                    setTimeout(() => {
                        this.createConfetti($(element));
                    }, index * 500);
                });
            }
        },

        initScrollAnimations: function() {
            // Use Intersection Observer for performance
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            $(entry.target).addClass('animate-in');
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '50px'
                });

                this.cache.$birthdayLists.find('li').each(function() {
                    observer.observe(this);
                });
            }
        },

        handleWidgetUpdate: function(e, widget) {
            // Re-cache elements after widget update
            if ($(widget).hasClass('widget_bp_birthdays')) {
                setTimeout(() => {
                    this.cacheElements();
                    this.initTooltips();
                    this.initAccessibility();
                    this.initSpecialEffects();
                }, 100);
            }
        },

        handleResize: function() {
            // Handle any responsive adjustments if needed
            this.optimizeLayout();
            this.repositionTooltips();
        },

        handleVisibilityChange: function() {
            // Pause animations when page is not visible
            if (document.hidden) {
                this.cache.$birthdayWidgets.addClass('paused-animations');
            } else {
                this.cache.$birthdayWidgets.removeClass('paused-animations');
            }
        },

        handleScroll: function() {
            // Handle scroll-based optimizations
            this.optimizeVisibleElements();
        },

        optimizeLayout: function() {
            // Optimize layout for current viewport
            const isMobile = window.innerWidth <= 768;
            const isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;
            
            this.cache.$birthdayLists
                .toggleClass('mobile-layout', isMobile)
                .toggleClass('tablet-layout', isTablet);
                
            // Adjust avatar sizes for smaller screens
            if (isMobile) {
                this.cache.$birthdayLists.find('.avatar, .avatar-link').addClass('small-avatar');
            } else {
                this.cache.$birthdayLists.find('.avatar, .avatar-link').removeClass('small-avatar');
            }
        },

        optimizeVisibleElements: function() {
            // Only animate elements that are visible
            const viewportTop = this.cache.$window.scrollTop();
            const viewportBottom = viewportTop + this.cache.$window.height();
            
            this.cache.$birthdayWidgets.each(function() {
                const $widget = $(this);
                const elementTop = $widget.offset().top;
                const elementBottom = elementTop + $widget.height();
                
                const isVisible = elementBottom > viewportTop && elementTop < viewportBottom;
                $widget.toggleClass('in-viewport', isVisible);
            });
        },

        repositionTooltips: function() {
            // Reposition visible tooltips after resize
            this.cache.$sendWishesButtons.find('.tooltip_wishes.visible').each((index, element) => {
                const $tooltip = $(element);
                const $button = $tooltip.closest('.send_wishes');
                this.positionTooltip($button, $tooltip);
            });
        },

        trackWishEvent: function($button) {
            // Enhanced analytics tracking
            const userName = $button.closest('li').find('strong a').text() || 'Unknown';
            
            // Google Analytics 4
            if (typeof gtag !== 'undefined') {
                gtag('event', 'birthday_wish_sent', {
                    event_category: 'engagement',
                    event_label: 'buddypress_birthdays',
                    custom_parameters: {
                        user_name: userName,
                        widget_location: this.getWidgetLocation($button)
                    }
                });
            }
            
            // Universal Analytics fallback
            if (typeof ga !== 'undefined') {
                ga('send', 'event', 'Birthday Wishes', 'Send', userName);
            }
            
            // Custom tracking
            if (typeof window.customBirthdayTracking === 'function') {
                window.customBirthdayTracking('wish_sent', {
                    user: userName,
                    timestamp: new Date().toISOString()
                });
            }
        },

        getWidgetLocation: function($button) {
            // Determine widget location for analytics
            const $widget = $button.closest('.widget_bp_birthdays');
            
            if ($widget.closest('.sidebar').length) return 'sidebar';
            if ($widget.closest('.footer').length) return 'footer';
            if ($widget.closest('.header').length) return 'header';
            return 'content';
        },

        showMessage: function(message, type = 'info') {
            // Enhanced message display with better UX
            const messageClass = `bp-birthday-message bp-birthday-${type}`;
            const $message = $(`<div class="${messageClass}" role="alert">${message}</div>`);
            
            // Find the best location to show the message
            let $container = $('.widget_bp_birthdays').first();
            if ($container.length === 0) {
                $container = $('body');
            }
            
            $message.prependTo($container)
                .hide()
                .fadeIn(this.settings.fadeSpeed)
                .delay(3000)
                .fadeOut(this.settings.fadeSpeed, function() {
                    $(this).remove();
                });
        },

        createNotification: function(title, message, options = {}) {
            // Browser notification for important birthday alerts
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: message,
                    icon: options.icon || '/wp-content/plugins/buddypress-birthdays/assets/images/birthday-icon.png',
                    badge: options.badge || '/wp-content/plugins/buddypress-birthdays/assets/images/birthday-badge.png',
                    tag: 'birthday-notification',
                    requireInteraction: false,
                    ...options
                });
                
                setTimeout(() => notification.close(), 5000);
                return notification;
            }
        },

        requestNotificationPermission: function() {
            // Request notification permission for birthday alerts
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        this.showMessage('Birthday notifications enabled!', 'success');
                    }
                });
            }
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        preloadImages: function() {
            // Preload important images for better performance
            const imagesToPreload = [
                '/wp-content/plugins/buddypress-birthdays/assets/images/birthday-icon.png',
                '/wp-content/plugins/buddypress-birthdays/assets/images/birthday-badge.png'
            ];
            
            imagesToPreload.forEach(src => {
                const img = new Image();
                img.src = src;
            });
        },

        destroy: function() {
            // Clean up event listeners and resources
            this.cache.$document.off('.bpBirthdays');
            this.cache.$window.off('.bpBirthdays');
            this.cache.$sendWishesButtons.off('.tooltip');
            
            // Clear any running timeouts
            if (this.tooltipTimer) {
                clearTimeout(this.tooltipTimer);
            }
            
            // Remove added classes
            this.cache.$birthdayLists.removeClass('mobile-layout tablet-layout');
            this.cache.$birthdayWidgets.removeClass('paused-animations in-viewport');
        },

        // Public API methods
        refresh: function() {
            this.destroy();
            this.init();
        },

        updateSettings: function(newSettings) {
            this.settings = $.extend(this.settings, newSettings);
        },

        getTodaysBirthdays: function() {
            return this.cache.$todayBirthdays.length;
        },

        getUpcomingBirthdays: function() {
            return this.cache.$birthdayLists.find('li').length;
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        BPBirthdays.init();
        BPBirthdays.preloadImages();
        
        // Initialize notification permission request (optional)
        if (BPBirthdays.getTodaysBirthdays() > 0) {
            setTimeout(() => {
                BPBirthdays.requestNotificationPermission();
            }, 2000);
        }
    });

    // Handle page unload cleanup
    $(window).on('beforeunload', function() {
        BPBirthdays.destroy();
    });

    // Expose to global scope for external access
    window.BPBirthdays = BPBirthdays;

    // Additional utility functions for birthday widgets
    const BirthdayUtils = {
        formatDate: function(dateString, format = 'F j') {
            // Enhanced date formatting utility
            const date = new Date(dateString);
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            
            const shortMonths = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];
            
            switch (format) {
                case 'F j':
                    return `${months[date.getMonth()]} ${date.getDate()}`;
                case 'M j':
                    return `${shortMonths[date.getMonth()]} ${date.getDate()}`;
                case 'j F':
                    return `${date.getDate()} ${months[date.getMonth()]}`;
                case 'j M':
                    return `${date.getDate()} ${shortMonths[date.getMonth()]}`;
                default:
                    return date.toLocaleDateString();
            }
        },

        calculateAge: function(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        },

        isToday: function(dateString) {
            const today = new Date();
            const date = new Date(dateString);
            
            return today.getDate() === date.getDate() && 
                   today.getMonth() === date.getMonth();
        },

        isTomorrow: function(dateString) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const date = new Date(dateString);
            
            return tomorrow.getDate() === date.getDate() && 
                   tomorrow.getMonth() === date.getMonth();
        },

        getDaysUntilBirthday: function(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            const currentYear = today.getFullYear();
            
            let nextBirthday = new Date(currentYear, birth.getMonth(), birth.getDate());
            
            if (nextBirthday < today) {
                nextBirthday.setFullYear(currentYear + 1);
            }
            
            const diffTime = nextBirthday - today;
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        },

        getUpcomingBirthdays: function(birthdays, limit = 5) {
            const today = new Date();
            const currentYear = today.getFullYear();
            
            return birthdays
                .map(birthday => {
                    const birthDate = new Date(birthday.date);
                    const thisYearBirthday = new Date(currentYear, birthDate.getMonth(), birthDate.getDate());
                    
                    // If birthday has passed this year, use next year
                    if (thisYearBirthday < today) {
                        thisYearBirthday.setFullYear(currentYear + 1);
                    }
                    
                    return {
                        ...birthday,
                        nextBirthday: thisYearBirthday,
                        daysUntil: Math.ceil((thisYearBirthday - today) / (1000 * 60 * 60 * 24)),
                        isToday: this.isToday(birthday.date),
                        isTomorrow: this.isTomorrow(birthday.date)
                    };
                })
                .sort((a, b) => a.nextBirthday - b.nextBirthday)
                .slice(0, limit);
        },

        getBirthdayGreeting: function(name, age) {
            const greetings = [
                `Happy Birthday, ${name}! 🎉`,
                `Wishing you a wonderful ${age}th birthday, ${name}! 🎂`,
                `Hope your special day is amazing, ${name}! 🎈`,
                `Many happy returns, ${name}! 🎁`,
                `Have a fantastic birthday, ${name}! ✨`
            ];
            
            return greetings[Math.floor(Math.random() * greetings.length)];
        },

        generateBirthdayMessage: function(name, age) {
            const messages = [
                `Hi ${name}! Wishing you a very happy ${age}th birthday! Hope your day is filled with joy and celebration! 🎉`,
                `Happy Birthday ${name}! May this new year of life bring you happiness, health, and all your heart desires! 🎂`,
                `Dear ${name}, Happy ${age}th Birthday! Hope you have a wonderful day surrounded by family and friends! 🎈`,
                `${name}, wishing you the happiest of birthdays! May ${age} be your best year yet! 🎁`
            ];
            
            return messages[Math.floor(Math.random() * messages.length)];
        }
    };

    // Expose utilities globally
    window.BirthdayUtils = BirthdayUtils;

    // Add CSS classes for enhanced animations
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .bp-birthday-message {
                padding: 12px 16px;
                margin: 10px 0;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
            }
            
            .bp-birthday-info {
                background: #dbeafe;
                color: #1e40af;
                border-left: 4px solid #3b82f6;
            }
            
            .bp-birthday-success {
                background: #dcfce7;
                color: #166534;
                border-left: 4px solid #22c55e;
            }
            
            .bp-birthday-error {
                background: #fee2e2;
                color: #dc2626;
                border-left: 4px solid #ef4444;
            }
            
            .birthday-celebrate {
                animation: celebrate 0.6s ease-out;
            }
            
            @keyframes celebrate {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
            
            .animate-in {
                animation: slideInUp 0.4s ease-out;
            }
            
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .small-avatar {
                width: 40px !important;
                height: 40px !important;
            }
            
            .paused-animations * {
                animation-play-state: paused !important;
            }
            
            .tooltip-right {
                left: auto !important;
                right: 0 !important;
                transform: translateX(0) !important;
            }
        `)
        .appendTo('head');

})(jQuery);

// Performance monitoring (development only)
if (typeof performance !== 'undefined' && console.time) {
    console.time('BP Birthdays Init');
    
    jQuery(document).ready(function() {
        console.timeEnd('BP Birthdays Init');
        
        if (window.BPBirthdays) {
            console.log('BP Birthdays Widget loaded successfully');
            console.log('Today\'s birthdays:', window.BPBirthdays.getTodaysBirthdays());
            console.log('Upcoming birthdays:', window.BPBirthdays.getUpcomingBirthdays());
        }
    });
}