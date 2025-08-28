Espo.define('custom:views/csms/edit', ['views/edit'], function(Dep) {
    return Dep.extend({
        setup: function() {
            Dep.prototype.setup.call(this);

            this.addMenuItem('buttons', {
                name: 'sendSms',
                label: 'Send SMS',
                onClick: function(e) {
                    if (e && e.stopPropagation) {
                        e.stopPropagation();
                    }
                    this.prepareSms();
                }.bind(this),
                style: 'success',
                iconHtml: '<span class="far fa-paper-plane"></span>',
                title: 'Send SMS Message',
            }, true);


        },


        afterRender: function() {


            // get the phone number from parent

            this.listenTo(this.model, 'change:parentId', function(model, value) {

                var parentType = model.get('parentType');
                var parentId = model.get('parentId');

                this.fetchPhoneParentPhoneNumber(parentType, parentId);

                console.log('parentId changed to:', value);
                console.log('parentType is:', model.get('parentType'));


            });


            this.phoneInputBooster();

            const $breadcrumb = this.$el.find('div.breadcrumb-item');
            if ($breadcrumb.length) {
                $breadcrumb.find('span:contains("criado")').html(
                    'Preencha os campos para enviar SMS <span class="far fa-paper-plane icon-highlight" style="margin-left: 8px; animation: moveLeftRight 2s ease-in-out infinite;"></span>'
                );

                // apply some style
                const style = document.createElement('style');
                style.textContent = `
        @keyframes moveLeftRight {
            0% { transform: translateX(0); }
            25% { transform: translateX(4px); }
            75% { transform: translateX(-4px); }
            100% { transform: translateX(0); }
        }
        
        .icon-highlight {
            color: #28a745;
            display: inline-block;
        }
    `;
                document.head.appendChild(style);

            }

            //<div class="breadcrumb-item"><span>criado</span></div>

            const $savePanel = this.$el.find('div.btn-group.actions-btn-group[role="group"]');
            if ($savePanel.length) {
                $savePanel.hide();
            }

            /*
            //hides 'save' button
            const $buttonSaveSms = this.$el.find('.btn.action.btn-xs-wide.detail-action-item.btn-primary.radius-left[data-action="save"]');
            if ($buttonSaveSms.length) {
                $buttonSaveSms.prop('disabled', true);
            }*/
        },



        fetchPhoneParentPhoneNumber: function(parentType, parentId) {
            var endpoint = `${parentType}/${parentId}`;
            console.log('Endpoint : ' + endpoint);

            Espo.Ajax.getRequest(endpoint).then(data => {
                console.log(data);

                // Check if phoneNumber exists in different possible locations
                var phoneNumber = data.phoneNumber ||
                    data.mobilePhoneNumber ||
                    (data.phoneNumbers && data.phoneNumbers[0] && data.phoneNumbers[0].phoneNumber);

                if (phoneNumber) {
                    this.model.set('phoneNumber', phoneNumber);
                } else {
                    console.warn('Phone number not found in response');
                }
            }).catch(xhr => { // xhr is the XMLHttpRequest object
                console.error('Error fetching phone number. Status:', xhr.status, 'Response:', xhr.responseText);
            });
        },

        prepareSms: function() {
            // Store values directly from DOM before any model operations
            var name = this.getFieldValue('name');
            var phoneNumber = this.getFieldValue('phoneNumber');
            var smsText = this.getFieldValue('smsText');

            // Store values
            this.pendingSmsData = {
                name: name,
                phoneNumber: phoneNumber,
                smsText: smsText
            };

            var errorMessages = [];
            var error = false;
            var field = [];

            if (!smsText) {
                field.push('smsText');
                errorMessages.push('Escreva um SMS entre 3 a 166 caracteres');
                error = true;

            } else if (smsText.length < 3) {
                field.push('smsText');
                errorMessages.push('A mensagem é muito curta');
                error = true;
            } else if (smsText.length >= 166) {
                field.push('smsText');
                errorMessages.push('A mensagem é muito longa');
                error = true;
            }

            if (!name) {
                field.push('name');
                errorMessages.push('Escreva o nome!');
                error = true;
            }

            if (!phoneNumber || !this.onlyDigits(phoneNumber)) {
                field.push('phoneNumber');
                errorMessages.push('O telefone/telemóvel está incorrecto');
                error = true;
            }

            if (error) {
                Espo.Ui.error(errorMessages.join('\n'));

                field.forEach(eachField => {
                    if (eachField.length > 0) {
                        this.showError(eachField);
                    }
                });
                return false;
            } else {

                setTimeout(() => {
                    this.model.set({
                        name: name,
                        phoneNumber: phoneNumber,
                        smsText: smsText
                    });

                    // Save the model first
                    this.saveModel().then(() => {
                        // After successful save, send SMS using the stored values
                        this.sendSms(this.pendingSmsData);
                        delete this.pendingSmsData;
                    }).catch(error => {
                        Espo.Ui.error('Erro ao guardar: ' + error.message);
                        delete this.pendingSmsData;
                    });
                }, 3000);
            }

        },



        phoneInputBooster: function() {
            const self = this;
            const $phoneInput = this.$el.find('[data-name="phoneNumber"]');

            $phoneInput.on('input', function() {
                var rawPhone = $(this).val();
                var hasPlus = rawPhone.startsWith('+');



                $phoneInput.on('keydown', function(event) {

                    const value = rawPhone;
                    const key = event.key;
                    const selectionStart = $phoneInput.selectionStart;
                    const selectionEnd = $phoneInput.selectionEnd;

                    const allowedKeys = [
                        'Backspace', 'Enter', 'ArrowLeft', 'ArrowRight',
                        'Delete', 'Tab'
                    ];


                    if (allowedKeys.includes(key)) {
                        // Special handling for Backspace when cursor is at position 1
                        if (key === 'Backspace' && selectionStart === 1 && selectionEnd === 1) {
                            // permite o backspace apagar o sinal +
                            return true;
                        }
                        return;
                    }


                    //permite o + só no inicio
                    if (key === '+') {
                        // If cursor is not at start or there's already a plus sign
                        if (selectionStart !== 0 || value.includes('+')) {
                            event.preventDefault();
                        }
                        return;
                    }

                    if (!/^\d$/.test(key)) {
                        event.preventDefault();
                        return;
                    }

                    if (value.startsWith('+') && selectionStart === 0) {
                        event.preventDefault();
                        return;
                    }


                    if (value.startsWith('+') && value.length === 13 && selectionStart === selectionEnd) {
                        event.preventDefault();
                    } else if (!value.startsWith('+') && value.length >= 12 && selectionStart === selectionEnd) {
                        event.preventDefault();
                    }


                    if (allowedKeys.includes(key) ||
                        event.ctrlKey && ['c', 'v', 'x', 'a'].includes(event.key.toLowerCase()) // ctrl+C , ctrl+V ...
                    ) {
                        event.preventDefault();
                        return;
                    }



                });




                $(this).val(rawPhone);
                console.log('Formatted:', rawPhone);

            });
        },

        onlyDigits: function(input) {

            digitsOnly = input.replace('/\D/g', '');
            return digitsOnly.length > 9;
        },




        saveModel: function() {
            return new Promise((resolve, reject) => {
                // Use EspoCRM's model save method
                this.model.save({}, {
                    success: () => {
                        resolve();
                    },
                    error: (model, xhr) => {
                        var errorMessage = 'Erro ao guardar';

                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.error && response.error.message) {
                                errorMessage = response.error.message;
                            }
                        } catch (e) {
                            // If we can't parse the response, use default message
                        }

                        reject(new Error(errorMessage));
                    }
                });
            });
        },

        sendSms: function(smsData) {
            var name = smsData.name;
            var phone = smsData.phoneNumber;
            var smsText = smsData.smsText;

            // Add more robust null checks
            if (!name && !phone && !smsText) {
                Espo.Ui.error('Nome , nº de telefone e texto SMS estão vazios');
                return;
            }
            if (!name) {
                Espo.Ui.error('Nome está vazio');
                return;
            }

            if (!phone) {
                Espo.Ui.error('Número de telefone está vazio');
                return;
            }

            if (!smsText) {
                Espo.Ui.error('Texto SMS está vazio');
                return;
            }

            // Convert to string safely before calling trim()
            var requestData = {
                name: String(name || '').trim(),
                phone: String(phone || '').trim(),
                sms: String(smsText || '').trim(),
                id: this.model.id // Use the saved model's ID
            };

            Espo.Ajax.postRequest('CSMS/action/SendSms', requestData)
                .then(response => {
                    if (response.status === 'error') {
                        Espo.Ui.error('SMS não enviado: ' + response.message);
                        return;
                    }

                    Espo.Ui.success('SMS enviado com sucesso!');

                    console.log(response);

                    // refresh to get any updates from server
                    this.model.fetch();
                })
                .catch(error => {
                    let errorMsg = 'Erro ao enviar SMS: ';
                    errorMsg += error.message || error.statusText || 'Erro desconhecido';
                    Espo.Ui.error(errorMsg);
                });
        },

        showError: function(fieldName) {
            var field = this.$el.find('[data-name="' + fieldName + '"]');

            var button = this.$el.find('[role=button].btn.btn-success.btn-xs-wide.main-header-manu-action.action.radius-left.radius-right');

            if (field.length) {
                var input = field.find('input, textarea');
                if (input.length) {
                    input.css('border', '1px solid red');
                    setTimeout(function() {
                        input.css('border', '');
                    }, 3000);
                }
            }

            if (button.length) {
                button.css('background-color', '#f90202ff');
                button.css('border-color', '');

                setTimeout(function() {
                    button.css('background-color', '#28a745');
                    button.css('border-color', '');
                }, 3000);
            }

        },

        // Helper method to get field values directly from DOM
        getFieldValue: function(fieldName) {
            // Try different selectors to find the field
            var selectors = [
                '[data-name="' + fieldName + '"] input',
                '[data-name="' + fieldName + '"] textarea',
                '[name="' + fieldName + '"]',
                'input[name="' + fieldName + '"]',
                'textarea[name="' + fieldName + '"]'
            ];

            for (var i = 0; i < selectors.length; i++) {
                var element = this.$el.find(selectors[i]);
                if (element.length) {
                    return element.val();
                }
            }

            return null;
        }
    });
});