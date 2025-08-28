define('custom:views/csms/list', ['views/list'],
    function(Dep) {

        return Dep.extend({

            setup: function() {

                Dep.prototype.setup.call(this);

            }


        });
    });