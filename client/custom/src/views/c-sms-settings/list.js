define('custom:views/c-sms-settings/list', ['views/list'],
    function(Dep) {

        return Dep.extend({

            setup: function() {
                Dep.prototype.setup.call(this);
                this.disabledCreate = true;


            },

            afterRender: function() {
                Dep.prototype.afterRender.call(this);
                this.removeCreateButton();
                this.disableEditable();

            },

            removeCreateButton: function() {


                var self = this;

                // Try multiple times with increasing delays
                var attempts = 0;
                var maxAttempts = 10;

                var tryToRemove = function() {
                    attempts++;


                    var selectors = [
                        'button[data-name="create"]',
                        'button[data-action="create"]',
                        'a[data-name="create"]',
                        'a[data-action="create"]'
                    ];

                    var found = false;

                    for (var i = 0; i < selectors.length; i++) {
                        var button = $(selectors[i]);
                        if (button.length) {
                            button.remove();
                            console.log('Create button removed with selector: ' + selectors[i]);
                            found = true;
                            break;
                        }
                    }

                    if (!found && attempts < maxAttempts) {
                        // Try again after a short delay
                        setTimeout(tryToRemove, 300);
                    } else if (!found) {
                        console.warn('Create button not found after ' + maxAttempts + ' attempts');
                    }
                };

                // Start trying to remove the button
                setTimeout(tryToRemove, 100);
            },




            // disallow editables from checkboxes
            disableEditable: function() {
                // Use event delegation for dynamically created elements


                /*this.$el.on('click', '.record-checkbox', function(e) {

                        console.log('Checkbox clicked:', $(this).data('id'),
                            'Checked:', $(this).is(':checked'));

                        // hides the button that allows the option to edit  records
                        var $dropdownButton = $('button.btn.btn-default.btn-xs-wide.dropdown-toggle.actions-button[data-toggle="dropdown"]');
                        if ($dropdownButton.length) {
                            $dropdownButton.hide();
                        }

                });*/
                this.$el.on('click', '.record-checkbox', (e) => {

                    if (!this.getUser().isAdmin()) {
                        console.log('Checkbox clicked:', $(e.target).data('id'),
                            'Checked:', $(e.target).is(':checked'));

                        var $dropdownButton = $('button.btn.btn-default.btn-xs-wide.dropdown-toggle.actions-button[data-toggle="dropdown"]');
                        if ($dropdownButton.length) {
                            $dropdownButton.hide();
                        }
                    }
                });

                // For the dropdown button specifically
                this.$el.on('click', '.btn.btn-link.btn-sm.dropdown-toggle', function(e) {
                    if (!this.getUser().isAdmin()) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Dropdown disabled for all users besides admin');
                        return false;
                    }
                }.bind(this));

                // Alternatively, hide the dropdown completely for admin
                if (!this.getUser().isAdmin()) {
                    this.$el.find('.list-row-buttons').hide();



                    // Or disable specific actions in the dropdown
                    this.$el.find('a[data-action="quickEdit"], a[data-action="quickRemove"]')
                        .parent().hide();

                }
            },

            // Clean up event listeners when view is removed
            onRemove: function() {
                this.$el.off('click', '.record-checkbox');
                this.$el.off('click', '.btn.btn-link.btn-sm.dropdown-toggle');
                Dep.prototype.onRemove.call(this);
            }
        });
    });