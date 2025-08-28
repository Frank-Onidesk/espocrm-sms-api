define('custom:views/csms/record/edit', ['views/record/edit'], function(Dep) {

    return Dep.extend({

        setup: function() {
            Dep.prototype.setup.call(this);

            this.addButton({
                name: 'customButton',
                label: 'Custom Action',
                style: 'alert',
                onClick: function(e) {
                    e.stopPropagation();
                    this.customAction();
                }.bind(this),
            }, true);
        },

        customAction: function() {
            // Your custom logic here
            console.log('Custom button clicked on CSMS create view');
            alert('Custom action triggered!');

            // Example: Get field values
            const name = this.model.get('name');
            console.log('CSMS name:', name);

            // Example: Perform custom validation or action
            // this.save();
        }

    });
});