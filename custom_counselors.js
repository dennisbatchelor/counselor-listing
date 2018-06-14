jQuery(document).ready(function() {
	jQuery("table").tablesorter( {sortList: [[2,0], [1,0]], widthFixed: true, widgets: ['zebra']} );
	jQuery("table").tablesorterPager( {container: jQuery("#pager")} );
	jQuery("img.first").click();
});
