
	var actionStatus = 'unrezzed'; // The state of the page. Goes to 'rezzed' when you hit the 'rez' button. 

	var timeoutMilliseconds = 30000; // How long until we give up waiting and try again. NB even after this a response may still show up, so we need to be able to handle it.
	var pendingRequests = new Object(); // A list of entries that we've made a request for, and the timestamp in milliseconds when we made them
	var numPendingRequests = 0; // Should correspond to the number of elements in pendingRequests

	var maxPendingRequests = 2; // Max outstanding requests we should have at one time. NB if requests time out but still return, there may be more outstanding.

	var statusPollingInterval= 5; // How often we should poll the server for activeobject updates

	var lastActiveObjectPollMillisecondTS = 0; // Millisecond TS for when we last polled the server for active object changes

	var timer; // Timer var used to coordinate the eventLoop, which checks for outstanding tasks and kicks them off.

	function purgeRequestList() {
	// TODO: Purge the request list of things that have timed out.
	// That will cause the script to try them again next time they get their turn in the eventLoop.
		for (entryid in pendingRequests) {
			if ( ( pendingRequests[""+entryid] + timeoutMilliseconds ) < ( new Date().getTime() ) ) {
				if ( $('#layoutentryid_'+entryid).hasClass('rezzing') ) {
					$('#layoutentryid_'+entryid).removeClass('rezzing').addClass('rezzing_timed_out');
				} else if ( $('#layoutentryid_'+entryid).hasClass('derezzing') ) {
					$('#layoutentryid_'+entryid).removeClass('derezzing').addClass('derezzing_timed_out');
				}
				delete pendingRequests[""+entryid];
				numPendingRequests--;
			}
		}	
	}

	function update_labels() {
	// Make the text of the status descriptions match their CSS classes.
	// (There's probably a cleaner way to do this - maybe better to have the text always be there and visibility depends on the CSS class?
		$('li.waiting_to_rez span.rezzable_item_status').html('Waiting to rez');
		$('li.rezzing_timed_out span.rezzable_item_status').html('Timed out');
		$('li.rezzed span.rezzable_item_status').html('Rezzed');
		$('li.rezzing span.rezzable_item_status').html('Rezzing');
		$('li.rezzing_failed span.rezzable_item_status').html('Rez Failed');
		$('li.derezzing_failed span.rezzable_item_status').html('Derez Failed');
		$('li.waiting_to_derez span.rezzable_item_status').html('Waiting to derez');
		$('li.derezzing_timed_out span.rezzable_item_status').html('Timed out');
		$('li.derezzed span.rezzable_item_status').html('Derezzed');
		$('li.derezzing span.rezzable_item_status').html('Derezzing');
		$('li.configured span.rezzable_item_status').html('Ready');
	}

	function start_waiting_tasks( itemspan, itemspanjq) {
	// Do whatever needs doing for an item.
	// If the class is "waiting_to" something, do that something.
		var bits = itemspan.id.split("_").pop().split("-"); // layoutentryid_1-2-3 becomes an Array(1,2,3)
		var entryid = bits.pop();	
		var layoutid = bits.pop();	
		var controllerid = bits.pop();	
		if ( itemspanjq.hasClass( 'waiting_to_rez' ) ) {
			if (!pendingRequests[""+entryid]) {
				numPendingRequests++;
			}
			pendingRequests[""+entryid] = new Date().getTime();	
			itemspanjq.removeClass( 'waiting_to_rez' );
			itemspanjq.addClass( 'rezzing' );
			rez_layout_item( itemspanjq, entryid, controllerid );
		} else if ( itemspanjq.hasClass( 'waiting_to_derez' ) ) {
			if (!pendingRequests[""+entryid]) {
				numPendingRequests++;
			}
			pendingRequests[""+entryid] = new Date().getTime();	
			itemspanjq.removeClass( 'waiting_to_derez' );
			itemspanjq.addClass( 'derezzing' );
			derez_layout_item( itemspanjq, entryid, controllerid );
		}

	}

	function check_done_tasks() {
	// Check if all the waiting tasks are complete. 
	// If they are, we can change the actionStatus.
		if (actionStatus == 'rezzing') {
			if ( ( $('li.rezzing').length == 0 ) && ( $('li.waiting_to_rez').length == 0 ) ) {
				actionStatus = 'rezzed';
				$('#rez_all_objects').html('Derez all objects');
				$('#rez_all_objects').unbind('click');
				$('#rez_all_objects').click(function() {
					start_derez_all();
				});
			}
		} else if (actionStatus == 'derezzing') {
			if ( ( $('li.derezzing').length == 0 ) && ( $('li.waiting_to_derez').length == 0 ) ) {
				actionStatus = 'derezzed';
				$('#rez_all_objects').html('Rez all objects');
				$('#rez_all_objects').unbind('click');
				$('#rez_all_objects').click(function() {
					start_rez_all();
				});
			}
		}

	}

	function eventLoop() {

		clearTimeout( timer );

		// Make the text labels match the CSS classes
		update_labels();

		// Clear out any timed-out requests.
		purgeRequestList();

		if (numPendingRequests < maxPendingRequests) { // Go easy on the server / rezzer
			// Check for anything that needs to be done
			$('.rezzable_item').each(function(itempos,itemspan) {
				if (numPendingRequests < maxPendingRequests) { 
					start_waiting_tasks( itemspan, $(this) );	
				}
			});
		}
		
		check_done_tasks();

		update_labels();

		timer = setTimeout( 'eventLoop()', 10000 );

	}

	function rez_layout_item(itemjq, entryid, controllerid) {
		$.getJSON(  
			"rez_object.php",  
			{
				layoutentryid: entryid,
				rezzeruuid: rezzer_uuid,
				controllerid: controllerid
			},  
			function(json) {  
				var result = json.result;
				if (result == 'rezzed') {
					itemjq.removeClass('rezzing').addClass('rezzed');;
				} else if (result == 'failed') {
					itemjq.removeClass('rezzing').addClass('rezzing_failed');;
				}
				if (pendingRequests[""+entryid]) {
					delete pendingRequests[""+entryid];
					numPendingRequests--;
				}
				eventLoop();
			}  
		);  
	}

	function derez_layout_item(itemjq, entryid, controllerid) {
		$.getJSON(  
			"derez_object.php",  
			{
				layoutentryid: entryid,
				rezzeruuid: rezzer_uuid,
				controllerid: controllerid
			},  
			function(json) {  
				var result = json.result;
				if (result == 'derezzed') {
					itemjq.removeClass('derezzing').addClass('derezzed');;
					if (itemjq.hasClass('deleted_from_layout')) {
						itemjq.remove(); // TODO: Remove the config form too
					}
				} else if (result == 'failed') {
					itemjq.removeClass('rezzing').addClass('derezzing_failed');;
				}
				if (pendingRequests[""+entryid]) {
					delete pendingRequests[""+entryid];
					numPendingRequests--;
				}
				eventLoop();

			}  
		);  
	}

	function start_derez_all() {
		actionStatus= 'derezzing';
		$('li.rezzed').addClass( 'waiting_to_derez' );
		$('li.waiting_to_derez').removeClass('rezzed');
		$('#rez_all_objects').html('Stop derezzing objects');
		$('#rez_all_objects').unbind('click');
		$('#rez_all_objects').click(function() {
			stop_derez_all();	
		});
		eventLoop();
	}

	function start_rez_all() {
		actionStatus= 'rezzing';
		$('li.derezzed').removeClass('derezzed');
		$('li.rezzable_item').not('li.rezzed').addClass( 'waiting_to_rez' );
		$('#rez_all_objects').html('Stop rezzing objects');
		$('#rez_all_objects').unbind('click');
		$('#rez_all_objects').click(function() {
			stop_rez_all();	
		});
		eventLoop();
	}

	function stop_derez_all() {
		$('li.waiting_to_derez').addClass( 'rezzed' ).removeClass('waiting_to_derez');;
		$('#rez_all_objects').html('Derez all objects');
		$('#rez_all_objects').unbind('click');
		$('#rez_all_objects').click(function() {
			start_derez_all();
		});
		eventLoop();
	}

	function stop_rez_all() {
		$('li.rezzable_item').removeClass( 'waiting_to_rez' );
		$('#rez_all_objects').html('Rez all objects');
		$('#rez_all_objects').unbind('click');
		$('#rez_all_objects').click(function() {
			start_rez_all();
		});
		eventLoop();
	}

	function add_to_layout( buttonjq ) {

		var frmjq = buttonjq.closest("form");
		buttonjq.html( buttonjq.attr('data-adding-text') );
		$.getJSON(  
			"add_layout_entry.php",  
			frmjq.serialize(),
			function(json) {  
				var result = json.result;
				var objectgroup = json.objectgroup;
				var objectname = json.objectname;
				var layoutid = json.layoutid;
				var layoutentryid = json.layoutentryid;
				if (result == 'added') {
					//alert('added');
					buttonjq.html( buttonjq.attr('data-add-text') );
					insert_layout_into_layout_divs( layoutid, layoutentryid, objectname, objectgroup, frmjq);
					//history.back();
					history.go(-2);
				} else if (result == 'failed') {
					//alert('Adding layout entry failed');
					buttonjq.html( buttonjq.attr('data-add-text') );
				}
			}  
		);  
		return false;

	}

	// NB This just removes the object from the layout and marks it for deletion.
	// If rezzed, we'll do the actual of the object with another request.
	function delete_layout_configuration( buttonjq ) {

		var frmjq = buttonjq.closest("form");
		buttonjq.html( buttonjq.attr('data-deleting-text') );
		$.getJSON(  
			"delete_layout_entry.php",  
			frmjq.serialize(),
			function(json) {  
				var result = json.result;
				var layoutentryid = json.layoutentryid;
				if (result == 'deleted') {
					//alert('deleted');
					buttonjq.html( buttonjq.attr('data-delete-text') ); 
					regexPtn = '^layoutentryid_.+-.+-.+-'+layoutentryid+'$';
					var re = new RegExp(regexPtn,"");
					$('li').filter(function() {
						return this.id.match(regexPtn);
					}).each( function() {
						//$(this).remove();
						// TODO: What happens if we're rezzing or waiting to rez?
						// Mark it as deleted and queue it for derezzing, then let the eventLoop deal with it in its own time
						if ($(this).hasClass('rezzed')) {
							$(this).addClass('waiting_to_derez');
							$(this).addClass('deleted_from_layout');
						} else { // Not rezzed, just get rid of it
							$(this).remove();
						}
					});
					history.back();
				} else { //if (result == 'failed') {
					alert('Deleting layout entry failed');
					buttonjq.html( buttonjq.attr('data-delete-text') );
				} 
			}  
		);  
		return false;

	}

	function update_layout_configuration( buttonjq ) {

		var frmjq = buttonjq.closest("form");
		buttonjq.html( buttonjq.attr('data-updating-text') );
		$.getJSON(  
			"update_layout_entry.php",  
			frmjq.serialize(),
			function(json) {  
				var result = json.result;
				var objectgroup = json.objectgroup;
				var layoutentryid = json.layoutentryid;
				if (result == 'updated') {
					//alert('updated');
					buttonjq.html( buttonjq.attr('data-update-text') );
					//update_layout_div( layoutid, layoutentryid );
					history.back();
					//history.go(-2);
				} else { //if (result == 'failed') {
					alert('Adding layout entry failed');
					buttonjq.html( buttonjq.attr('data-update-text') );
				} 
			}  
		);  
		return false;

	}

	function insert_layout_into_layout_divs( layoutid, layoutentryid, objectname, objectgroup, addfrmjq ) {

		regexPtn = '^layout_.+-.+-'+layoutid+'$';
		var re = new RegExp(regexPtn,"");
		$('ul').filter(function() {
			return this.id.match(regexPtn);
		}).each( function() {
			// make an id for the new element	
			var newElementID = $(this).attr('id').replace('layout_','layoutentryid_')+'-'+layoutentryid;

			// make a list item for the layout screen, and insert it at the bottom of its group.
			var newItem = '<li id="'+newElementID+'" class="rezzable_item"><a href="#configure_'+newElementID+'">'+objectname+'<span style="float:right; margin-right:10px; color:grey; font-style:italic" class="rezzable_item_status">&nbsp;</span> </a></li>'

			// If we don't yet have a group to put this item in, create it
			if ( $(this).children(".after_group_"+objectgroup).size() == 0 ) {
				var groupLi = '<li class="group">'+objectgroup+'</li>' + '<li class="after_group_'+objectgroup+'"></li>';
				$(this).children(".add_object_group").before( groupLi );
			}

			$(this).children(".after_group_"+objectgroup).before(newItem);

			// Make a copy of the add form and change it into an edit form
			var editFrm = addfrmjq.clone(); 
			editFrm.attr('id', 'configure_'+newElementID); 
                        editFrm.children("input[name='layoutentryid']").val(layoutentryid);  // set the layoutentryid hidden field
			editFrm.attr('selected', ''); // Remove the selected property so that iui hides the form
			editFrm.children('.add_to_layout_button').addClass('update_layout_entry_button').removeClass('add_to_layout_button');
			editFrm.children('.update_layout_entry_button').html( editFrm.children('.update_layout_entry_button:first').attr('data-update-text') );
			editFrm.children('.delete_layout_entry_button').removeClass('hiddenButton');

			$('#add_configuration_above_me').before(editFrm).html();

			editFrm.click(function() {
				return update_layout_configuration($(this));
			});

		});
		
	}

	function configure_set( itemspanjq ) {
		var bits = itemspanjq.attr('id').split("_").pop().split("-"); // layoutentryid_1-2-3 becomes an Array(1,2,3)
		var controllerid = bits.pop();
		$.getJSON(  
			"configure_rezzer.php",  
			{
				controllerid: controllerid,
				rezzeruuid: rezzer_uuid
			},
			function(json) {  
				var result = json.result;
				if (result == 'configured') {
					$('#set_configuration_status').hide();
					$('#rez_all_objects').show();	
				} else if (result == 'failed') {
					$('#rez_all_objects').hide();	
				}
			}  
		);  
		return true;
	}

	$(document).ready(function () {
		$('.layout_link').click(function() {
			return configure_set($(this));
		});
		$('#rez_all_objects').hide();
		$('#rez_all_objects').click(function() {
			start_rez_all();	
		});
		$('.add_to_layout_button').click(function() {
			return add_to_layout($(this));
		});
		$('.update_layout_entry_button').click(function() {
			return update_layout_configuration($(this));
		});
		$('.delete_layout_entry_button').click(function() {
			return delete_layout_configuration($(this));
		});
	});
