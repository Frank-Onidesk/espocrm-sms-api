Espo.define('custom:views/csms/detail', ['views/detail'], function(Dep) {
    return Dep.extend({
        setup: function() {
            Dep.prototype.setup.call(this);
            /*this.addMenuItem('buttons', {
                name: 'sendSms',
                label: 'Send SMS',
                style: 'btn btn-primary'
            }, true);*/
        }
    });
});