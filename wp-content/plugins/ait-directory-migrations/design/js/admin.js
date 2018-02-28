jQuery(document).ready(function(){

	jQuery('.ait_migration_options #saveSettings').on('click', function(e){		
		e.preventDefault();
		if(confirm('Are you sure ?')){
			// okey
			var formdata = {};
			jQuery('.ait_migration_options').find('select').each(function(){
				var id = jQuery(this).attr('name');
				var val = jQuery(this).val();

				formdata[id] = val;
			});

			jQuery.post(ajaxurl, {
				'action': 'aitMigrationSaveSettings',
				'data': formdata
			}).done(function(xhr){
				if(xhr.status.fail === false){
					window.location.reload();
				}				
			}).fail(function(xhr){
				// server fail
			});
		}		
	});	

	jQuery('.ait_migration_options #migration-start').on('click', function(e){
		e.preventDefault();
		if(confirm('Are you sure ?')){
			jQuery('.ait_migration_options .ait-loader').removeClass('loader-hidden');
			jQuery('.ait_migration_options .ait-migration-timer').removeClass('timer-hidden');

			AitMigration.start();
		}
	});	

	/*jQuery('.ait_migration_options #migration-stop').on('click', function(e){
		e.preventDefault();
		AitMigration.stop();
	});*/

});

jQuery(window).on('beforeunload', function(){
	AitMigration.stop();
});

/* MIGRATION OBJECT SETUP */
AitMigration = {};

AitMigration.interval = null;
AitMigration.working = false;
AitMigration.bulkCount = 1; 			// defaults to 1
AitMigration.bulkCountDecrement = 1; 	// defaults to 5

AitMigration.maxExecutionTime = 30;
AitMigration.maxExecutionTimeGuard = AitMigration.maxExecutionTime / 6;	// 1/6 time second guard

AitMigration.timer = {};

AitMigration.timer.request = {};
AitMigration.timer.request.start = null;
AitMigration.timer.request.end = null;

AitMigration.timer.average = {};
AitMigration.timer.average.data = [];
AitMigration.timer.average.value = 0;

AitMigration.start = function(){
	AitMigration.update();
	AitMigration.interval = setInterval(function(){
		if(AitMigration.working == false){
			// dynamic request checker
			// last request time * bulkCount => new request time .. if this exceed 25 seconds .. update bulkCount to less items	
			if(AitMigration.timer.average.data.length > 0){
				// only if we have last request data we can modify the bulkCounts
				var lastRequestItemTime = AitMigration.timer.average.data[AitMigration.timer.average.data.length-1];
				if((lastRequestItemTime * AitMigration.bulkCount)/1000 > (AitMigration.maxExecutionTime-AitMigration.maxExecutionTimeGuard) ){
					// decrement by decrementer
					if(AitMigration.bulkCount - AitMigration.bulkCountDecrement < 1){
						AitMigration.bulkCount = 1;
					} else {
						AitMigration.bulkCount = AitMigration.bulkCount - AitMigration.bulkCountDecrement;	
					}
				} else {
					// increment by decrementer
					AitMigration.bulkCount = AitMigration.bulkCount + AitMigration.bulkCountDecrement;
				}
			}

			AitMigration.migrate();
		}
	}, 100);
}

AitMigration.stop = function(){
	clearInterval(AitMigration.interval);
}

AitMigration.migrate = function(){
	AitMigration.working = true;
	
	AitMigration.timer.request.start = new Date().getTime();

	if(AitMigration.bulkCount == 1){
		var data = false;
		if(typeof AitMigration.commands[0] != 'undefined'){
			data = {};
			data['cmd'] = AitMigration.commands[0][0];
			data['id'] = AitMigration.commands[0][1];
		} else {
			AitMigration.stop();
			window.location.reload();
		}
		
		if(data != false){
			jQuery.post(ajaxurl, {
				'action': 'aitMigrateOperation',
				'data': data
			}).done(function(xhr){
				AitMigration.commands = xhr.commands;
				
				AitMigration.timer.request.end = new Date().getTime();				
				AitMigration.updateTimers();

				AitMigration.update();					

				AitMigration.working = false;			
			}).fail(function(xhr){
				console.log(xhr);
			});
		}
	} else {
		var data = false;
		
		if(AitMigration.commands.length != 0){
			var bulkMax = AitMigration.commands.length > AitMigration.bulkCount ? AitMigration.bulkCount : AitMigration.commands.length;
			var data = [];
			for(var i = 0; i < bulkMax; i++){
				var operation = {};
				operation['cmd'] = AitMigration.commands[i][0];
				operation['id'] = AitMigration.commands[i][1];
				data.push(operation);
			}
		} else {
			AitMigration.stop();
			window.location.reload();
		}
				
		if(data != false){
			jQuery.post(ajaxurl, {
				'action': 'aitMigrateOperationBulk',
				'data': data
			}).done(function(xhr){
				AitMigration.commands = xhr.commands;
				
				AitMigration.timer.request.end = new Date().getTime();				
				AitMigration.updateTimers();

				AitMigration.update();				

				AitMigration.working = false;			
			}).fail(function(xhr){
				console.log(xhr);
			});
		}
	}
}

AitMigration.settingsReset = function(){
	if(confirm('Are you sure ?')){
		jQuery.post(ajaxurl, {
			'action': 'aitMigrationResetSettings'
		}).done(function(xhr){
			console.log(xhr);
			window.location.reload();			
		}).fail(function(xhr){
			console.log(xhr);
		});
	}
}

AitMigration.update = function(){
	var $loader = jQuery('.ait_migration_options .ait-loader');
	var $status = $loader.find('.loader-status');
	var $bar = $loader.find('.loader-bar')
	var $timer = jQuery('.ait_migration_options .ait-migration-timer');

	$loader.attr('data-value', ((AitMigration.loaderTotal - AitMigration.commands.length) * AitMigration.loaderStep) );
	
	var value = Math.round($loader.attr('data-value'));			// user visible value will be 12
	var percentage = parseFloat($loader.attr('data-value'));	// enable to show 12.123456%
	
	if(value >= 51){
		$status.removeClass('loader-all-letter');
		$status.addClass('loader-all');
	} else if(value >= 50){
		$status.removeClass('loader-first-letter');
		$status.addClass('loader-all-letter');
	} else if(value >= 49){
		$status.addClass('loader-first-letter');
	} else {
		// nic
		$status.removeClass('loader-first-letter');
		$status.removeClass('loader-all-letter');
		$status.removeClass('loader-all');
	}

	$status.find('.loader-value').html(value);
	$bar.css({'width': percentage+"%"});


	/*var timeLeft = (AitMigration.timer.average.value * (AitMigration.commands.length / AitMigration.bulkCount) ) / 1000; // in seconds*/
	var timeLeft = (AitMigration.timer.average.value * AitMigration.commands.length) / 1000; // in seconds
	var timerValue = 0;
	var timerUnit = '';
	if(timeLeft/3600 > 1){
		// use hours
		timerValue = timeLeft / 3600;
		timerUnit = 'hour(s)';
	} else if(timeLeft / 60 > 1){
		// use minutes
		timerValue = timeLeft / 60;
		timerUnit = 'minute(s)';
	} else {
		// use seconds
		timerValue = timeLeft;
		timerUnit = 'second(s)';
	}

	$timer.find('.timer-value').html(parseInt(timerValue));
	$timer.find('.timer-unit').html(timerUnit);
}

AitMigration.updateTimers = function(){
	AitMigration.timer.average.data.push( (AitMigration.timer.request.end - AitMigration.timer.request.start) / AitMigration.bulkCount );
	var timerTotal = 0;
	for(var i = 0, len = AitMigration.timer.average.data.length; i < len; i++) {
		timerTotal += AitMigration.timer.average.data[i];
	}
	// get time for a single operation and multiply by operations count; because sometimes there is a request for 10 operations and sometimes for 20 op
	AitMigration.timer.average.value = timerTotal / AitMigration.timer.average.data.length;
}
/* MIGRATION OBJECT SETUP */