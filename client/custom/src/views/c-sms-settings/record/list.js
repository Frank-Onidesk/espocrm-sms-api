Espo.define('custom:views/c-sms-settings/record/list', ['views/record/list'], function(Dep) {

    return Dep.extend({
        setup: function() {
            Dep.prototype.setup.call(this);
            this.disabledCreate = true;
        },

        afterRender: function() {
            Dep.prototype.afterRender.call(this);

            // Remove the create button completely
            this.removeCreateButton();
        },

        removeCreateButton: function() {
            var self = this;

            // Try to remove the button with a small delay
            setTimeout(function() {
                var selectors = [
                    'button[data-name="create"]',
                    'button[data-action="create"]'
                ];

                for (var i = 0; i < selectors.length; i++) {
                    var button = $(selectors[i]);
                    if (button.length) {
                        button.remove();
                        console.log('Create button removed');
                        break;
                    }
                }
            }, 300);
        }
    });
});