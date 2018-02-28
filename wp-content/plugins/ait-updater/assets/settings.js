(function(){

	new Vue({
		el: '#vue-root',

		data: jQuery.extend(_AitUpdaterSettings, {
			savingState: '',
			showFixDirnameNotice: true,
			hasErrors: {
				username: false,
				api_key: false
			},
			inputErrors: {
				username: {},
				api_key: {}
			}
		}),


		mounted: function(){
			this.initOnfOffSwitch();
		},


		watch: {
			'fields.do_backup': function (v){
				if(this.switchify){
					var type = v === 1 ? 'on' : 'off';
					var controls = this.switchify.data('controls')[type]();
				}
			},
			'fields.username': function(value){
				this.hasErrors.username = (value === '');
				this.inputErrors.username = {};
				this.inputErrors.username.empty = this.validationErrors.username.empty;
			},
			'fields.api_key': function(value){
				this.hasErrors.api_key = (value === '');
				this.inputErrors.api_key = {};
				this.inputErrors.api_key.empty = this.validationErrors.api_key.empty;
			}
		},


		methods: {

			onSubmit: function(e){
				var data = JSON.parse(JSON.stringify(this.$data));
				data.fields['action'] = 'aitUpdater:saveSettings';
				this.saveSettings(data);
			},

			saveSettings: function(data){
				var vm = this;
				vm.savingState = 'action-working';

				if(this.hasErrors.username || this.hasErrors.api_key){
					vm.savingState = 'action-error';
					setTimeout(function(){
						vm.savingState = '';
					}, 2000);
					return;
				}

				jQuery
					.post(ajaxurl, data.fields)
					.done(function(response){
						vm.savingState = 'action-done';
						if(response.success){
							vm.wpNotice = response.data;
						}else{
							for(var e in response.data){
								var rule = response.data[e]
								for(var r in rule){
									Vue.set(vm.inputErrors[e], rule[r], vm.validationErrors[e][rule[r]]);
								}
							}
						}

						setTimeout(function(){
							vm.savingState = '';
						}, 2000);
					})
					.fail(function(response){
						vm.savingState = 'action-error';
						console.log(response);
					});
			},

			fixDirname: function(){
				var vm = this;
				jQuery
					.post(ajaxurl, {'action': 'aitUpdater:fixDirname'})
					.done(function(response){
						vm.wpNotice = response.data;
						vm.showFixDirnameNotice = false;
					})
					.fail(function(response){
						console.log(response);
					});
			},

			initOnfOffSwitch: function(){
				var vm = this;
				jQuery(function(){
					if(jQuery.fn.switchify === undefined) return;
					vm.switchify = jQuery('select#do_backup_field').switchify().data('switch').on('switch:slide', function(e, type){
						vm.fields.do_backup = type === 'on' ? 1 : 0;
					});
				});
			}
		}
	});
})();