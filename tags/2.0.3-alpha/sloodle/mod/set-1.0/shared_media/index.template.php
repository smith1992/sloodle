<?php 
function print_home_javascript() {
?>
<html>
<head>
<script language="JavaScript">
window.home();
</script>
</head>
</html>
<?php
}

function print_html_top($loadfrom = '') { 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= s(get_string('rezzer:sloodlesetup', 'sloodle')) ?></title>
<link rel="apple-touch-icon" href="iui/iui-logo-touch-icon.png" />
<style type="text/css" media="screen">@import "<?=$loadfrom?>iui/iui_avatarclassroom.css";</style>
<style type="text/css" media="screen">@import "<?=$loadfrom?>layout.css";</style>
<script type="application/x-javascript" src="../../../lib/jquery/jquery.js"></script>
<script type="application/x-javascript" src="layout.js?<?= time() ?>"></script>
<!--
-->
<script type="text/javascript">
	var rezzer_uuid = '<?= htmlentities($_REQUEST['sloodleobjuuid']) ?>';
</script>
</head>

<body>

<?php
}

function print_toolbar( $baseurl, $sitesURL ) {
?>
    <div class="toolbar">
        <h1 id="pageTitle"></h1>
        <a id="backButton" class="button" href="#sitelist"><?= ( $sitesURL != '' ) ? s(get_string('rezzer:avatarclassroom', 'sloodle')) : '' ?></a>
        <a class="button" onclick="document.location.href = '<?= $baseurl.'&logout=1&ts='.time()?>'" href="<?= $baseurl.'&logout=1&ts='.time()?>"><?= s(get_string('rezzer:logout', 'sloodle')) ?></a>
    </div>
<?php
}

// A placeholder div 
// Never actually loaded - we intercept "sitelist" and use it to redirect to the api. site.
function print_site_placeholder( $sitesURL ) {
?>
	<div id="sitelist" data-parent-url="<?= $sitesURL ?>" title="<?= ( $sitesURL != '' ) ? s(get_string('rezzer:avatarclassroom', 'sloodle')) : '' ?>"></div>
<?php
}

function print_site_list( $sites ) {
?>
     
    <ul id="sitelist" title="<?= s(get_string('rezzer:avatarclassroom','sloodle')) ?>" selected="true">
        <li class="group"><?= s(get_string('rezzer:sites', 'sloodle')) ?></li>
	<?php foreach($sites as $site) { ?>
	<?php if ('http://'.$_SERVER["SERVER_NAME"] == $site) { ?>
        <li><a class="active_site_link" href="#site_1"><?=$site?></a></li>
	<?php } else { ?>
        <li><a href="<?=$site?>"><?=$site?></a></li>
	<?php } ?>
	<?php } ?>
	<li></li>
        <li class="group"><?= s(get_string('rezzer:addsite', 'sloodle'))?></li>
        <li><a href="#addsite"><?= s(get_string('rezzer:addsite', 'sloodle'))?></a></li>
	<li ></li>
    </ul>

<?php 
}

function print_controller_list( $courses, $controllers, $hasSites, $sitesURL, $hasCourses, $hasControllers, $hasControllersWithPermission) {
$hasSites = false;
$full = false;
?>

    <ul id="site_1" data-parent="sitelist" title="<?= "http://".$_SERVER["SERVER_NAME"]?>" <?= $hasSites ? '' : ' selected="true"' ?> >

<div class="upper_button_zone">
<span class="left_zone">

	<span class="title_text"><?= s( get_string('rezzer:controllers','sloodle')) ?></span>
</span>
<span class="right_zone">


<span class="control_button reload_page_button" type="submit"><?= s(get_string('rezzer:refreshconfig', 'sloodle', $object_title)) ?></span>

</span>
</div>


	<?php if (!$hasControllersWithPermission) { ?>
		<?php if (!$hasCourses) { ?>
			<li><?=s(get_string('rezzer:nocourses','sloodle'))?></li>
		<?php } else if (!$hasControllers) { ?>
			<li><?=s(get_string('rezzer:nocontrollers','sloodle'))?></li>
		<?php } else if (!$hasControllersWithPermission) { ?>
			<li><?=s(get_string('rezzer:nocontrollerswithpermission','sloodle'))?></li>
		<?php } ?>
	<?php } ?>

	<?php 
	foreach($courses as $course) { 
		$cid = $course->id; 
		$cn = $course->fullname; 
		if (isset($controllers[$cid])) { 
			foreach($controllers[$cid] as $contid=>$cont) {
?>
				<li><a href="#controller_<?= intval($cid)?>-<?= intval($contid) ?>"><?= s( $cn ) ?> <?= s( $cont->name ) ?></a></li>
<?php
			}
		}
	}
?>
<?php if ($full) { ?>
	<li></li>
        <li class="group"><?= s(get_string('rezzer:addcontroller', 'sloodle'))?></li>
        <li><a href="#addcontroller"></a></li>
	<li></li>
<?php } ?>
    </ul>

<?php if ($full) { ?>
     
    <form data-parent="site_1" id="addcontroller" class="panel" title="Add a Course">
    <fieldset>
       <div class="row" style="height:60px;">
          <label for="course_name" name="course_name"><?= s(get_string('rezzer:name', 'sloodle'))?></label>
          <input id="course_name" name="course_name" class="panel" style="width:80%; height:40px; margin:10px;">
       </div>
       <div class="row" style="height:60px;">
          <label for="course"><?= s(get_string('rezzer:course'))?></label>
          <select id="course" class="az" name="course" style="height:40px; width:80%; margin:10px;">
	  <option value="1">Japanese For Beginners</option>
        <option value="2">Spanish For Dummies</option>
      </select></div>
	<a class="active_button" type="submit" href="#">Add Course</a>
    </fieldset>
    </form>

<?php } 

}

function print_layout_list( $courses, $controllers, $courselayouts ) {

	foreach($courses as $course) {
		$cid = $course->id; 
		$cn = $course->fullname; 
		if (!isset($controllers[$cid])) {
			continue;
		}
		foreach($controllers[$cid] as $contid => $cont) {
?>
    <ul data-parent="site_1" id="controller_<?= intval($cid)?>-<?= intval($contid) ?>" title="<?= s( $cn ) ?> <?= s( $cont->name ) ?>" class="controllercourselayouts_<?= intval($cid)?>" data-id-prefix="layout_<?= intval($cid)?>-<?= intval($contid) ?>-">
        <li class="group">Scenes</li>
<?php
			$layouts = $courselayouts[ $cid ][$contid];
			foreach($layouts as $layout) {
?>
        <li data-layout-link-li-id="<?= intval($layout->id) ?>" ><a class="layout_link" href="#layout_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>"><?= s($layout->name) ?></a></li>
<?php
			}
?>
	<li class="add_layout_above_me"></li>
        <li class="group"><?= s(get_string('rezzer:addlayout', 'sloodle'))?></li>
        <li><a class="add_layout_link" href="#addlayout_<?= intval($cid) ?>-<?= intval($contid) ?>"><?= s(get_string('rezzer:addlayout', 'sloodle'))?></a></li>
    </ul>
<?php 

		}

	}

}


function print_add_layout_forms( $courses, $controllers, $rezzeruuid ) {
	foreach($courses as $course) {
		$cid = $course->id;
		foreach($controllers[$cid] as $contid => $cont) {
?>
    <form data-parent="controller_<?= intval($cid)?>-<?= intval($contid) ?>" id="addlayout_<?= intval($cid)?>-<?= intval($contid) ?>" class="panel" title="<?= s(get_string('rezzer:addlayout', 'sloodle'))?> <?= s($course->fullname) ?>">

<div class="upper_button_zone">
<span class="left_zone">
                                <span class="title_text"><?= s( get_string('rezzer:createscene','sloodle')) ?></span>

</span>
<span class="right_zone">

	<span data-creating-text="<?= s(get_string('rezzer:creatingscene', 'sloodle'))?>" data-create-text="<?= s(get_string('rezzer:createscenetitle','sloodle'))?>" class="control_button create_layout_button" type="submit" href="#"><?= s(get_string('rezzer:createscene','sloodle'))?></span>

</span>
</div>

	<input type="hidden" name="courseid" value="<?= intval($cid)?>" />
	<input type="hidden" name="rezzeruuid" value="<?= s($rezzeruuid)?>" />
	<input type="hidden" name="controllerid" value="<?= intval($contid)?>" />
	<fieldset>
	<div class="row" >
		<label for="layoutname"><?= s(get_string('rezzer:layoutname', 'sloodle'))?></label>
		<input id="layoutname" name="layoutname" class="panel" style="width:80%; height:40px; margin:10px;">
	</div>
	</fieldset>
    </form>
<?php
		}
	}
}

function print_html_bottom() {
?>
</body>
</html>
<?php
}
?>
<?php 
function print_layout_lists( $courses, $controllers, $courselayouts, $layoutentries, $rezzeruuid) {

	foreach($courses as $course) {
		$cid = $course->id; 
		$cn = $course->fullname; 
		if (!isset($controllers[$cid])) {
			continue;
		}
		foreach($controllers[$cid] as $contid => $cont) {
			$layouts = $courselayouts[ $cid ][$contid];
			foreach($layouts as $layout) {

				if ($layout->controllerid != $contid) {
					continue;
				}

				$hasactiveobjects = $layout->has_active_objects_rezzed_by_rezzer( $rezzeruuid );
				$entriesbygroup = $layoutentries[ $layout->id ];


				$rezzed_entries = $layout->rezzed_active_objects_by_layout_entry_id( $rezzeruuid );


?>
			    <ul data-parent="controller_<?= intval($cid)?>-<?= intval($contid) ?>" class="layout_container layout_container_<?= intval($layout->id) ?>" id="layout_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>" title="<?= htmlentities( $layout->name ) ?>" data-rez-mode="<?= $hasactiveobjects ? 'rezzed' : 'unrezzed'?>" data-action-status="<?= $hasactiveobjects ? 'rezzed' : 'unrezzed'?>" data-connection-status="disconnected">


				<li class="group"><?= s( $layout->name ) ?></li>

<li class="upper_button_zone">
<span class="left_zone">
				<span class="title_text rename_input_text"><?= s( $layout->name) ?></span>
				<span style="display:none" class="rename_input"><input size="20" maxlength="20" class="rename_layout_input" data-rename-input-layoutid="<?= intval($layout->id) ?>" value="<?= s( $layout->name ) ?>" /></span>
			
</span>

<span class="right_zone">

				<span class="control_button rename_layout_button" data-layoutid="<?= intval($layout->id) ?>" data-renamed-text="<?= s(get_string('rezzer:renamelayout'))?>" data-rename-text="<?= s(get_string('rezzer:renamelayout','sloodle'))?>" data-renaming-text="<?= s(get_string('rezzer:renaminglayout'))?>" class="control_button" type="submit" ><?= s(get_string('rezzer:renamelayout','sloodle'))?></span>

				<span class="control_button delete_layout_button" data-layoutid="<?= intval($layout->id) ?>" data-deleted-text="<?= s(get_string('rezzer:deletedderezzingobjects', 'sloodle')) ?>" data-delete-text="<?= s(get_string('rezzer:deletelayout'))?>" data-deleting-text="<?= s(get_string('rezzer:deletinglayout','sloodle'))?>" type="submit"><?= s(get_string('rezzer:deletelayout', 'sloodle'))?></span>
				<span class="control_button delete_layout_button_placeholder"></span>

				<span class="control_button clone_layout_button" data-layoutid="<?= intval($layout->id) ?>" data-cloned-text="<?= s(get_string('rezzer:clonelayout','sloodle'))?>" data-cloning-text="<?=s(get_string('rezzer:cloninglayout','sloodle'))?>" data-clone-text="<?=s(get_string('rezzer:clonelayout','sloodle'))?>" type="submit" ><?=s(get_string('rezzer:clonelayout','sloodle'))?></span>
									
				<span class="control_button sync_object_positions" data-freeze-text="<?= s(get_string('rezzer:savecurrentposition', 'sloodle'))?>" data-freezing-text="<?= s(get_string('rezzer:savingcurrentposition', 'sloodle'))?>" type="submit" href="#clonelayout"><?= s(get_string('rezzer:savecurrentposition', 'sloodle'))?></span>
				<span class="control_button sync_object_positions_placeholder"></span>

				<span id="set_configuration_status_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>" class="control_button button_goes_here_zone set_configuration_status"></span>
				<span id="rez_all_objects_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>" class="big_control_button rez_all_objects"> &nbsp; <?= s(get_string('rezzer:rezallobjects', 'sloodle'))?></span>

				<span id="generate_standard_layout_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>" data-generate-text="<?= s(get_string('rezzer:importmoodleactivities','sloodle', $cn )) ?>" data-generating-text="<?= s(get_string('rezzer:importingmoodleactivities', 'sloodle'))?>" data-layoutid="<?= intval($layout->id) ?>" class="big_control_button generate_standard_layout"><?= s(get_string('rezzer:importmoodleactivities', 'sloodle', $cn )) ?></span>

</span>
</li>
<?php
				foreach($entriesbygroup as $group => $entries) {
?>
					<li class="group"><?= htmlentities( get_string('objectgroup:'.$group, 'sloodle') ) ?></li>
<?php
					foreach($entries as $e) {

						$isrezzed = isset( $rezzed_entries[ $e->id ]);
				
//print "rezzedentries";
//var_dump($rezzed_entries);
						/*
						$firstletter = substr($entryname, 0, 1);
						if ($lettergroup != $firstletter) { 
							$lettergroup = $firstletter;
						}
						*/

						print_rezzable_item_li( $e, $cid, $contid, $layout, $isrezzed);
						


					}
?>
					<li class="after_group_<?=$group?>"><a class="add_object_link" href="#addobjectgroup_<?= $group ?>_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>"><?=s(get_string('rezzer:addobjects', 'sloodle'))?></a></li>
					<li></li>
<?php
				}
?>
			    </ul>

<?php
			}
		}
	}
?>
<span id="add_layout_lists_above_me"></span>
<?php
}

function print_rezzable_item_li( $e, $cid, $contid, $layout, $isrezzed) {

	$entryname = $e->name;	
	$entryname = preg_replace('/SLOODLE\s/', '', $entryname);

	$modTitle = $e->get_course_module_title();
	?>
<?php /*
					<li><a href="#<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>-<?= intval($e->id) ?>"><?= htmlentities($entryname) ?><span style="float:right; margin-right:10px; color:grey; font-style:italic" class="rezzable_item">Rezzed</span> <span style="float:right; margin-right:100px; color:grey; font-style:italic" class="rezzable_item">Moved</span></a></li>
	*/ ?>
							<?php /* NB If you change this, you also need to change layout.js, which creates some of these dynamically. */ ?>
							<li data-layoutentryid="<?= $e->id?>" id="layoutentryid_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>-<?=intval( $e->id ) ?>" class="rezzable_item <?= ( $isrezzed ? 'rezzed' : '' ) ?>"><a href="#configure_layoutentryid_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>-<?=intval( $e->id ) ?>"><?= s($entryname) ?><span class="module_info"><?=s($modTitle)?></span><span class="rezzable_item_status">&nbsp;</span> <span class="rezzable_item_positioning">&nbsp;</span> </a></li>
<?php
}

function print_add_object_item_li( $object_title, $config, $cid, $contid, $layout) {
	$object_title = preg_replace('/^SLOODLE /', '', $object_title);
	$id = "linkto_addobject_{$cid}-{$contid}-{$layout->id}_{$config->type_for_link()}";
?>
        <li id="<?=$id?>"><a href="#addobject_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>_<?= $config->type_for_link()?>"><?= s($object_title) ?></a></li>
<?php 
	return $id;

}

function print_layout_add_object_groups( $courses, $controllers, $courselayouts, $objectconfigsbygroup) {

	foreach($courses as $course) {
		$cid = $course->id; 
		$cn = $course->fullname; 
		if (!isset($controllers[$cid])) {
			continue;
		}
		foreach($controllers[$cid] as $contid => $cont) {
			$layouts = $courselayouts[ $cid ][$contid];
			foreach($layouts as $layout) {
?>
<?php 
				foreach($objectconfigsbygroup as $group => $groupobjectconfigs) {
?>
    <ul <?= $group == 'misc' ? 'class="object_group_misc"' : '' ?> data-parent="layout_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>" id="addobjectgroup_<?= $group ?>_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>" title="<?=s(get_string('rezzer:addobjectsforgroup', 'sloodle', $group) ) ?>">
        <li class="group"><?=s(get_string('rezzer:addobjectsforgroup', 'sloodle', $group) ) ?></li>
<?php
	foreach($groupobjectconfigs as $object_title => $config) {
		print_add_object_item_li( $object_title, $config, $cid, $contid, $layout );
	}

?>
        <li></li>

    </ul>
<?php
				}
			}
		}
	}
?>
<span id="add_add_object_groups_above_me"></span>
<?php
}

function print_add_object_forms($courses, $controllers, $courselayouts, $object_configs, $rezzeruuid ) {

	foreach($courses as $course) {
		$cid = $course->id; 
		$cn = $course->fullname; 
		if (!isset($controllers[$cid])) {
			continue;
		}
		foreach($controllers[$cid] as $contid => $cont) {
			if (!isset($courselayouts[ $cid ]) ) {
				continue;
			}
			$layouts = $courselayouts[ $cid ][$contid];
			foreach($layouts as $layout) {
				foreach($object_configs as $object_title => $config) {
/*
The following form is used for adding the object.
But once it's been added, it will be clone()d to make a form to update the object we added.
*/
					print_add_object_form( $config, $cid, $contid, $layout, $object_title, $rezzeruuid );
?>

<?php 
				}
			}
		}
	}
?>
<span id="add_add_object_forms_above_me"></span>
<?php
}

function print_add_object_form( $config, $cid, $contid, $layout, $object_title, $rezzeruuid ) {
	$id = "addobject_{$cid}-{$contid}-{$layout->id}_{$config->type_for_link()}";
?>
<form data-parent="addobjectgroup_<?= $config->group ?>_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>" class="add_object_form panel addobject_layout_<?= intval($layout->id) ?>_<?= $config->type_for_link()?>" id="<?=$id?>" title="<?= s($object_title) ?>" data-primname="<?= s($config->primname)?>" data-courseid="<?=intval($cid)?>"  >

<div class="upper_button_zone">
<span class="left_zone">
                                <span class="title_text"><?= s( $object_title) ?></span>

</span>
<span class="right_zone">

<span data-delete-text="<?= s(get_string('rezzer:deleteobject', 'sloodle', $object_title)) ?>" data-deleting-text="<?= s(get_string('rezzer:deletingobject', 'sloodle', $object_title)) ?>" class="control_button delete_layout_entry_button hiddenButton" type="submit"><?= s(get_string('rezzer:deleteobject', 'sloodle', $object_title)) ?></span>

<span data-refresh-text="<?= s(get_string('rezzer:refreshconfig', 'sloodle', $object_title)) ?>" data-refreshing-text="<?= s(get_string('rezzer:refreshingconfig', 'sloodle', $object_title)) ?>" class="control_button refresh_config_button" type="submit"><?= s(get_string('rezzer:refreshconfig', 'sloodle', $object_title)) ?></span>

<span data-updating-text="<?= s(get_string('rezzer:updatingobject', 'sloodle', $object_title )) ?>" data-update-text="<?= s(get_string('rezzer:updateobject', 'sloodle', $object_title )) ?>" data-adding-text="<?= s( get_string('rezzer:addingobject', 'sloodle', $object_title )) ?>" data-add-text="<?= s( get_string('rezzer:addobject', 'sloodle', $object_title )) ?>" class="control_button add_to_layout_button" target="_self" type="submit"><?= s( get_string('rezzer:addobject', 'sloodle', $object_title) )  ?></span>

</span>
</div>


<input type="hidden" name="rezzeruuid" value="<?= htmlentities($rezzeruuid) ?>" />
<input type="hidden" name="objectname" value="<?= htmlentities($object_title) ?>" />
<input type="hidden" name="objectgroup" value="<?= htmlentities($config->group) ?>" />
<input type="hidden" name="layoutid" value="<?= intval($layout->id) ?>" />
<input type="hidden" name="layoutentryid" value="0" />
<input type="hidden" name="controllerid" value="<?= intval($contid) ?>" />
<input type="hidden" name="courseid" value="<?= intval($cid) ?>" />
<?php if ($config->module) { 
$moduleoptionselect = $config->course_module_select( $cid, $val = null ); 
?>
<fieldset>
<div class="row">
<label for="<?= 'sloodlemoduleid' ?>"><?= $config->module_choice_message ? get_string($config->module_choice_message, 'sloodle') : ''?></label>
<span class="sloodle_config data-fieldname="sloodlemoduleid">
<?= $moduleoptionselect ? $moduleoptionselect : '<span class="no_options_placeholder" data-fieldname="sloodlemoduleid">'.( ( $config->module_no_choices_message != '' ) ? get_string($config->module_no_choices_message , 'sloodle') : '' ) .'</span>' ?>
</span>
</div>
</fieldset>
<?php
} ?>
<?php foreach($config->field_set_row_groups() as $fsrg) { ?>
<fieldset>
<?php foreach($fsrg as $ctrls) { ?>
<?php $ctrls_reversed = array_reverse($ctrls); ?>
<?php $first_ctrl = array_shift($ctrls); ?>
<div class="row" data-row-name="<?= isset($first_ctrl->row_name) ? $first_ctrl->row_name : '' ?>" >
<label for="<?= isset($first_ctrl->fieldname) ? $first_ctrl->fieldname : '' ?>"><?= $first_ctrl->title ? s(get_string($first_ctrl->title,'sloodle')) : ''?></label>
<?php foreach($ctrls_reversed as $ctrl) { ?>
<?php $fieldname = $ctrl->fieldname; ?>
<span class="sloodle_config object_<?= s($config->type_for_link())?>" data-courseid="<?=intval($cid)?>" data-fieldname="<?=$fieldname?>">
<?php if ( ($ctrl->type == 'radio') || ($ctrl->type == 'yesno') ) { ?>
<?php foreach($ctrl->options as $opn => $opv) { ?>
<input type="radio" name="<?= $fieldname ?>" value="<?= $opn ?>" <?= $opn == $ctrl->default ? 'checked ' : '' ?>> <?= $ctrl->is_value_translatable ? get_string($opv, 'sloodle') : s($opv) ?> &nbsp; &nbsp; 
<?php } ?>
<?php } else if ($ctrl->type == 'input') { ?>
<input type="text" size="<?= $ctrl->size ?>" maxlength="<?= $ctrl->max_length ?>" name="<?= $fieldname ?>" value="<?= $ctrl->default ?>" /> 
<?php } else { ?>
<?=$ctrl->type?>
<?php } ?>
</span>
<?php } ?>
</div>
<?php } ?>
</fieldset>
<?php } ?>
</form>

<br />

<?php
	return $id;
}

/*
Configuration form for each 
*/
function print_edit_object_forms($courses, $controllers, $courselayouts, $object_configs, $layoutentries, $rezzeruuid) {

	foreach($courses as $course) {
		$cid = $course->id; 
		$cn = $course->fullname; 
		foreach($controllers[$cid] as $contid => $cont) {
			$layouts = $courselayouts[ $cid ][$contid];
			foreach($layouts as $layout) {
				$entriesbygroup = $layoutentries[ $layout->id ];
				$lid = $layout->id;
				foreach($entriesbygroup as $group => $entries) {
					foreach($entries as $e) {
						$entryname = $e->name;	
						$config = $object_configs[$entryname]; // TODO: Merge in the layout entries

						print_config_form( $e, $config, $cid, $contid, $lid, $group, $rezzeruuid );
						
					}
				}
?>
<span id="add_configuration_above_me_layout_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($layout->id) ?>"></span>
<?php
			}
		}
	}
?>
<span id="add_edit_object_forms_above_me"></span>
<?php
}

function print_config_form( $e, $config, $cid, $contid, $lid, $group, $rezzeruuid ) {

						$lconfig = $e->get_layout_entry_configs_as_name_value_hash();
						$entryname = preg_replace('/SLOODLE\s/', '', $e->name);
						$object_title = $entryname;

	$id = "configure_layoutentryid_{$cid}-{$contid}-{$lid}-{$e->id}";

?>
<form data-parent="layout_<?= intval($cid)?>-<?= intval($contid) ?>-<?= intval($lid) ?>" id="<?=$id?>" class="panel edit_object_form" title="<?= s($object_title) ?>" data-primname="<?= s($config->primname)?>" data-courseid="<?=intval($cid)?>">


<div class="upper_button_zone">
<span class="left_zone">
                                <span class="title_text"><?= s( $object_title) ?></span>

</span>
<span class="right_zone">


<span data-delete-text="<?= s(get_string('rezzer:deleteobject', 'sloodle', $object_title)) ?>" data-deleting-text="<?= s(get_string('rezzer:deletingobject', 'sloodle', $object_title)) ?>" class="control_button delete_layout_entry_button" type="submit"><?= s(get_string('rezzer:deleteobject', 'sloodle', $object_title)) ?></span>

<span data-refresh-text="<?= s(get_string('rezzer:refreshconfig', 'sloodle', $object_title)) ?>" data-refreshing-text="<?= s(get_string('rezzer:refreshingconfig', 'sloodle', $object_title)) ?>" class="control_button refresh_config_button" type="submit"><?= s(get_string('rezzer:refreshconfig', 'sloodle', $object_title)) ?></span>

<span data-updating-text="<?= s(get_string('rezzer:updatingobject', 'sloodle', $object_title )) ?>" data-update-text="<?= s(get_string('rezzer:updateobject', 'sloodle', $object_title )) ?>" class="control_button update_layout_entry_button" target="_self" type="submit"><?= s(get_string('rezzer:updateobject', 'sloodle', $object_title )) ?></span>

</span>
</div>


<input type="hidden" name="layoutid" value="<?= intval($lid) ?>" />
<input type="hidden" name="rezzeruuid" value="<?= htmlentities($rezzeruuid) ?>" />
<input type="hidden" name="layoutentryid" value="<?= intval($e->id) ?>" />
<input type="hidden" name="controllerid" value="<?= intval($contid) ?>" />
<input type="hidden" name="objectgroup" value="<?= htmlentities( get_string('objectgroup:'.$group, 'sloodle' ) ) ?>" />
<?php if ($config->module) { 
$moduleoptionselect = $config->course_module_select( $cid, $lconfig['sloodlemoduleid'] ); 
?>
<fieldset>
<div class="row">
<label for="<?= isset($config->fieldname) ? $config->fieldname : '' ?>"><?= get_string($config->module_choice_message, 'sloodle') ?></label>
<span class="sloodle_config" data-courseid="<?=intval($cid)?>" data-fieldname="sloodlemoduleid" >
<?= $moduleoptionselect ? $moduleoptionselect : '<span class="no_options_placeholder" data-fieldname="sloodlemoduleid">'.( ( $config->module_no_choices_message != '' ) ?  get_string($config->module_no_choices_message, 'sloodle') : '').'</span>' ?>
</span>
</div>
</fieldset>
<?php
} ?>
<?php foreach($config->field_set_row_groups() as $fs => $fsrg) { ?>
<fieldset>
<?php if (count($fsrg) > 0) { ?>
<?php foreach($fsrg as $ctrls) { ?>
<?php $ctrls_reversed = array_reverse($ctrls); ?>
<?php $first_ctrl = array_shift($ctrls); ?>
<div class="row" data-row-name="<?= isset($first_ctrl->row_name) ? $first_ctrl->row_name : '' ?>" >
<label for="<?= $first_ctrl->fieldname ?>"><?= get_string($first_ctrl->title, 'sloodle') ?></label>
<?php foreach($ctrls_reversed as $ctrl) { ?>
<?php $fieldname = $ctrl->fieldname; ?>
<?php 	$val = isset($lconfig[$fieldname]) ? $lconfig[$fieldname] : ''; ?>
<span class="sloodle_config object_<?= s($config->type_for_link())?>" data-courseid="<?=intval($cid)?>" data-fieldname="<?=$fieldname?>" >
<?php if ( ($ctrl->type == 'radio') || ($ctrl->type == 'yesno') ) { ?>
<?php foreach($ctrl->options as $opn => $opv) { ?>
<input type="radio" name="<?= $fieldname ?>" value="<?= $opn ?>" <?= $opn == $val ? 'checked ' : '' ?>> <?= $ctrl->is_value_translatable ? get_string($opv, 'sloodle') : s($opv) ?> &nbsp; &nbsp; 
<?php } ?>
<?php } else if ($ctrl->type == 'input') {?>
<input type="text" size="<?= $ctrl->size ?>" maxlength="<?= $ctrl->max_length ?>" name="<?= $fieldname ?>" value="<?= $val ?>" /> 
<?php } else {?>
<?=$ctrl->type?>
<?php } ?>
</span>
<?php } ?>
</div>
<?php } ?>
<?php } ?>
</fieldset>
<?php } ?>
</form>

<br />
<?php 

	return $id;
}
?>
