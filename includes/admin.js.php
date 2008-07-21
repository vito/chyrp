<?php
	define('JAVASCRIPT', true);
	require_once "common.php";
?>
<!-- --><script>
$(function(){
	// Scan AJAX responses for errors.
	$(document).ajaxComplete(function(event, request){
		var response = request.responseText
		if (isError(response))
			alert(response.replace(/(HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW|<([^>]+)>\n?)/gm, ""))
	})<?php echo "\n\n\n\n\n"; # Balance out the line numbers in this script and in the output to help debugging. ?>

	// Handle typing "\ct" to insert a <tab>
	$("textarea").keyup(function(event){
		if ($(this).val().match(/([^\\]|^)\\ct/gm))
			$(this).val($(this).val().replace(/([^\\]|^)\\ct/gm, "	"))
	})

	// Automated PNG fixing.
	$.ifixpng("<?php echo $config->chyrp_url; ?>/admin/images/icons/pixel.gif")
	$("img[@src$=.png]").ifixpng()

	// "Help" links should open in popup windows.
	$(".help").click(function(){
		window.open($(this).attr("href"), "help", "status=0, height=350, width=300")
		return false;
	})

	// Checkbox toggling.
	togglers()

	if ($.browser.safari)
		$("code").each(function(){
			$(this).css({
				fontFamily: "Monaco, monospace",
				fontSize: "9px"
			})
		})

	if (/(edit|write)_/.test(Route.action))
		Write.init()

	if (Route.action == "manage_pages")
		Manage.pages.init()

	if (Route.action == "modules" || Route.action == "feathers")
		Extend.init()

	// Remove things that only exist for JS-disabled users.
	$(".js_disabled").remove()
	$(".js_enabled").css("display", "block")
})

function togglers() {
	var all_checked = true

	$("#toggler").html('<label for="toggle"><?php echo __("Toggle All"); ?></label><input class="checkbox" type="checkbox" name="toggle" id="toggle" />')

	$(".toggler").html('<input class="checkbox" type="checkbox" name="toggle" id="toggle" />')

	$("#toggle").click(function(){
		$("form#new_group, form#group_edit, table").find(":checkbox").not("#toggle").each(function(){
			this.checked = document.getElementById("toggle").checked
		})
	})

	$("form#new_group, form#group_edit").find(":checkbox").not("#toggle").each(function(){
		if (!all_checked) return
		all_checked = this.checked
	})

	if ($("#toggler").size())
		document.getElementById("toggle").checked = all_checked
}

Array.prototype.indicesOf = function(value) {
	var results = []

	for (var j = 0; j < this.length; j++)
		if (typeof value != "string") {
			if (value.test(this[j]))
				results.push(j)
		} else if (this[j] == value)
			results.push(j)

	return results
}

Array.prototype.find = function(match) {
	var matches = []

	for (var f = 0; f < this.length; f++)
		if (match.test(this[f]))
			matches.push(this[f])

	return matches
}

Array.prototype.remove = function(value) {
	if (value instanceof Array) {
		for (var r = 0; r < value.length; r++)
			this.remove(value[r])

		return
	}

	var indices = this.indicesOf(value)

	if (indices.length == 0)
		return

	for (var h = 0; h < indices.length; h++)
		this.splice(indices[h] - h, 1)

	return this
}

var Route = {
	action: "<?php echo $_GET['action']; ?>"
}

var Write = {
	init: function(){
		this.bookmarklet_link()
		this.auto_expand_fields()
		this.sortable_feathers()
		this.prepare_previewer()
		this.more_options()
	},
	bookmarklet_link: function(){
		$(document.createElement("li")).addClass("bookmarklet right").html("<?php echo _f("Bookmarklet: %s", array('<a class=\"no_drag\" href=\"javascript:var%20d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0)),f=\''.$config->chyrp_url.'/admin/?action=bookmarklet\',l=d.location,e=encodeURIComponent,p=\'&url=\'+e(l.href)+\'&title=\'+e(d.title)+\'&selection=\'+e(s),u=f+p;a=function(){if(!w.open(u,\'t\',\'toolbar=0,resizable=0,status=1,width=450,height=430\'))l.href=u;};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else%20a();void(0)\">Chyrp!</a>')); ?>").prependTo(".write_post_nav")
	},
	auto_expand_fields: function(){
		$("input.text").each(function(){
			if ($(this).parent().parent().attr("class") == "more_options") return
			$(this).css("min-width", $(this).outerWidth()).Autoexpand()
		})
		$("textarea").each(function(){
			$(this).css({
				minHeight: $(this).outerHeight() + 2,
				lineHeight: "15px"
			}).autogrow()
		})
	},
	sortable_feathers: function(){
		// Make the Feathers sortable
		$("#sub-nav").sortable({
			axis: "x",
			placeholder: "feathers_sort",
			opacity: 0.8,
			delay: 1,
			revert: true,
			cancel: "a.no_drag, a[href$=write_page]",
			update: function(){
				$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", "action=reorder_feathers&"+$("#sub-nav").sortable("serialize"))
			}
		})
	},
	prepare_previewer: function() {
		if ($(".preview_me").length > 0) {
			var feather = ($("#write_feather").size()) ? $("#write_feather").val() : ""
			var feather = ($("#edit_feather").size()) ? $("#edit_feather").val() : feather
			$(document.createElement("div")).css("display", "none").attr("id", "preview").insertBefore("#write_form, #edit_form")
			$(document.createElement("button")).html("<?php echo __("Preview &rarr;"); ?>").attr({ "type": "submit", "accesskey": "p" }).click(function(){
				$("#preview").load("<?php echo $config->chyrp_url; ?>/includes/ajax.php", { action: "preview", content: $(".preview_me").val(), feather: feather }, function(){
					$(this).fadeIn("fast")
				})
				return false
			}).appendTo(".buttons")
		}
	},
	more_options: function(){
		console.log($("#more_options").size())
		if ($("#more_options").size()) {
			if (Cookie.get("show_more_options") == "true")
				var more_options_text = "<?php echo __("&laquo; Fewer Options"); ?>";
			else
				var more_options_text = "<?php echo __("More Options &raquo;"); ?>";

			$(document.createElement("a")).attr({
				id: "more_options_link",
				href: "javascript:void(0)"
			}).addClass("more_options_link").html(more_options_text).insertBefore(".buttons")
			$("#more_options").clone().insertAfter("#more_options_link").removeClass("js_disabled")

			if (Cookie.get("show_more_options") == null)
				$("#more_options").css("display", "none")

			$("#more_options_link").click(function(){
				if ($("#more_options").css("display") == "none") {
					$(this).html("<?php echo __("&laquo; Fewer Options"); ?>")
					Cookie.set("show_more_options", "true", 30)
				} else {
					$(this).html("<?php echo __("More Options &raquo;"); ?>")
					Cookie.destroy("show_more_options")
				}
				$("#more_options").slideToggle()
			})
		}
	}
}

var Manage = {
	pages: {
		init: function(){
			Manage.pages.prepare_reordering()
		},
		parent_hash: function(){
			var parent_hash = ""
			$(".sort_pages li").each(function(){
				var id = $(this).attr("id").replace(/page_list_/, "")
				var parent = $(this).parent().parent() // this > #sort_pages > page_list_(id)
				var parent_id = (/page_list_/.test(parent.attr("id"))) ? parent.attr("id").replace(/page_list_/, "") : 0
				$(this).attr("parent", parent_id)
				parent_hash += "&parent["+id+"]="+parent_id
			})
			return parent_hash
		},
		prepare_reordering: function(){
			$(".sort_pages li").css({
				background: "#f9f9f9",
				padding: ".15em .5em",
				marginBottom: ".5em",
				border: "1px solid #ddd"
			})

			$(".sort_pages li, .page-item").css("cursor", "move")

			$(".sort_pages input, form#reorder_pages .buttons").remove()

			$("ul.sort_pages").attr("id", "sort_pages").NestedSortable({
				accept: "page-item",
				opacity: 0.8,
				nestingPxSpace: 5,
				onStop: function(){
					$("#content > form > ul.sort_pages").loader()
					$.post("<?php echo $config->url; ?>/includes/ajax.php",
					       "action=organize_pages&"+ $.SortSerialize("sort_pages").hash + Manage.pages.parent_hash(),
					       function(){ $("#content > form > ul.sort_pages").loader(true) })
				}
			})
		}
	}
}

var Extend = {
	init: function(){
		this.prepare_draggables()

		if (Route.action != "modules")
			return

		this.draw_conflicts()
		this.draw_dependencies()

		$(window).resize(function(){
			Extend.draw_conflicts()
			Extend.draw_dependencies()
		})
	},
	Drop: {
		extension: {
			classes: [],
			name: null,
			type: null
		},
		action: null,
		previous: null,
		pane: null,
		confirmed: null
	},
	prepare_draggables: function(){
		$(".enable h2, .disable h2").append(" <span class=\"sub\"><?php echo __("(drag)"); ?></span>")

		$(".disable > ul > li:not(.missing_dependency), .enable > ul > li").draggable({
			zIndex: 100,
			cancel: "a",
			revert: true
		})

		$(".enable > ul, .disable > ul").droppable({
			accept: "ul.extend > li:not(.missing_dependency)",
			tolerance: "pointer",
			activeClass: "active",
			hoverClass: "hover",
			drop: Extend.handle_drop
		})

		$(".info_link").click(function(){
			$(this).parent().find(".description").toggle("blind", {}, null, Extend.redraw)
			return false
		})

		$(".enable > ul > li, .disable > ul > li:not(.missing_dependency)").css("cursor", "move")
		$("ul.extend li .description:not(.expanded)").css("display", "none")

		Extend.equalize_lists()

		if ($(".feather").size())
			<?php $tip = _f("(tip: drag the tabs on the <a href=\\\"%s\\\">write</a> page to reorder them)", array(url("/admin/?action=write"))); ?>
			$(document.createElement("small")).html("<?php echo $tip; ?>").css({
				position: "relative",
				bottom: "-1em",
				display: "block",
				textAlign: "center"
			}).appendTo(".tip_here")
	},
	handle_drop: function(ev, ui) {
		var classes = $(this).parent().attr("class").split(" ")

		Extend.Drop.pane = $(this)
		Extend.Drop.action = classes[0]
		Extend.Drop.previous = $(ui.draggable).parent().parent().attr("class").split(" ")[0]
		Extend.Drop.extension.classes = $(ui.draggable).attr("class").split(" ")
		Extend.Drop.extension.name = Extend.Drop.extension.classes[0]
		Extend.Drop.extension.type = classes[1]

		Extend.Drop.confirmed = false

		if (Extend.Drop.previous == Extend.Drop.action)
			return

		$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", {
			action: "check_confirm",
			check: Extend.Drop.extension.name,
			type: Extend.Drop.extension.type
		}, function(data){
			if (data != "" && Extend.Drop.action == "disable")
				Extend.Drop.confirmed = (confirm(data)) ? 1 : 0

			$.ajax({
				type: "post",
				dataType: "json",
				url: "<?php echo $config->chyrp_url; ?>/includes/ajax.php",
				data: {
					action: Extend.Drop.action + "_" + Extend.Drop.extension.type,
					extension: Extend.Drop.extension.name,
					confirm: Extend.Drop.confirmed
				},
				beforeSend: function(){ Extend.Drop.pane.loader() },
				success: Extend.finish_drop
			})
		})

		$(ui.draggable).css({ left: 0, right: 0, top: 0, bottom: 0 }).appendTo(this)

		Extend.redraw()

		return true
	},
	finish_drop: function(json){
		if (Extend.Drop.action == "enable") {
			var dependees = Extend.Drop.extension.classes.find(/depended_by_(.+)/)
			for (i = 0; i < dependees.length; i++) {
				var dependee = dependees[i].replace("depended_by", "module")

				// The module depending on this one no longer "needs" it
				$("#"+ dependee).removeClass("needs_"+ Extend.Drop.extension.name)

				// Remove from the dependee's dependency list
				$("#"+ dependee +" .dependencies_list ."+ Extend.Drop.extension.name).hide()

				if ($("#"+ dependee).attr("class").split(" ").find(/needs_(.+)/).length == 0)
					$("#"+ dependee).find(".dependencies_message, .dependencies_list, .description").hide().end()
					                .draggable({
					                    zIndex: 100,
					                    cancel: "a",
					                    revert: true
					                })
					                .css("cursor", "move")
			}
		} else {
			$(".depends_"+ Extend.Drop.extension.name).find(".dependencies_message, .dependencies_list, .description").show()
			$(".depends_"+ Extend.Drop.extension.name)
				.find(".dependencies_list")
				.append($(document.createElement("li")).html(Extend.Drop.extension.name).addClass(Extend.Drop.extension.name))
				.end()
				.addClass("needs_"+ Extend.Drop.extension.name)
		}

		Extend.Drop.pane.loader(true)
		$(json.notifications).each(function(){
			if (this == "") return
			alert(this.replace(/<([^>]+)>\n?/gm, ""))
		})

		Extend.redraw()
	},
	equalize_lists: function(){
		$("ul.extend").height("auto")
		$("ul.extend").each(function(){
			if ($(".enable ul.extend").height() > $(this).height())
				$(this).height($(".enable ul.extend").height())

			if ($(".disable ul.extend").height() > $(this).height())
				$(this).height($(".disable ul.extend").height())
		})
	},
	redraw: function(){
		Extend.equalize_lists()
		Extend.draw_conflicts()
		Extend.draw_dependencies()
	},
	draw_conflicts: function(){
		if (!$(".extend li.conflict").size() && !($.browser.safari || $.browser.opera || ($.browser.mozilla && $.browser.version >= 1.9)))
			return false

		$("#conflicts_canvas").remove()

		$("#header, #welcome, #sub-nav, #content a.button, .extend li, #footer, h1, h2").css({
			position: "relative",
			zIndex: 2
		})
		$("#header ul li a").css({
			position: "relative",
			zIndex: 3
		})

		$(document.createElement("canvas")).attr("id", "conflicts_canvas").prependTo("body")
		$("#conflicts_canvas").css({
			position: "absolute",
			top: 0,
			bottom: 0,
			zIndex: 1
		}).attr({ width: $(document).width(), height: $(document).height() })

		var canvas = document.getElementById("conflicts_canvas").getContext("2d")
		var conflict_displayed = []

		$(".extend li.conflict").each(function(){
			var classes = $(this).attr("class").split(" ")
			classes.shift() // Remove the module's safename class

			classes.remove(["conflict",
			                "depends",
			                "missing_dependency",
			                /depended_by_(.+)/,
			                /needs_(.+)/,
			                /depends_(.+)/,
			                /ui-draggable(-dragging)?/])

			for (i = 0; i < classes.length; i++) {
				var conflict = classes[i].replace("conflict_", "module_")

				if (conflict_displayed[$(this).attr("id")+" :: "+conflict])
					continue;

				canvas.strokeStyle = "#ef4646"
				canvas.fillStyle = "#fbe3e4"
				canvas.lineWidth = 3

				var this_status = $(this).parent().parent().attr("class").split(" ")[0] + "d"
				var conflict_status = $("#"+conflict).parent().parent().attr("class").split(" ")[0] + "d"

				if (conflict_status != this_status) {
					var line_from_x = (conflict_status == "disabled") ? $("#"+conflict).offset().left : $("#"+conflict).offset().left + $("#"+conflict).outerWidth()
					var line_from_y = $("#"+conflict).offset().top + 12
					var line_to_x = (conflict_status == "enabled") ? $(this).offset().left : $(this).offset().left + $(this).outerWidth()
					var line_to_y   = $(this).offset().top + 12

					// Line
					canvas.moveTo(line_from_x, line_from_y)
					canvas.lineTo(line_to_x, line_to_y)
					canvas.stroke()

					// Beginning circle
					canvas.beginPath()
					if (conflict_status == "disabled")
						canvas.arc(line_from_x, line_from_y, 5, 1.35, -1.35, false)
					else
						canvas.arc(line_from_x, line_from_y, 5, -1.35, 1.35, false)
					canvas.fill()
					canvas.stroke()

					// Ending circle
					canvas.beginPath()
					if (conflict_status == "disabled")
						canvas.arc(line_to_x, line_to_y, 5, -1.75, 1.75, false)
					else
						canvas.arc(line_to_x, line_to_y, 5, 1.75, -1.75, false)
					canvas.fill()
					canvas.stroke()
				} else if (conflict_status == "disabled") {
					var line_from_x = $("#"+conflict).offset().left
					var line_from_y = $("#"+conflict).offset().top + 12
					var line_to_x   = $(this).offset().left
					var line_to_y   = $(this).offset().top + 12
					var median = line_from_y + ((line_to_y - line_from_y) / 2)
					var curve = line_from_x - 25

					// Line
					canvas.beginPath();
					canvas.moveTo(line_from_x, line_from_y)
					canvas.quadraticCurveTo(curve, median, line_to_x, line_to_y);
					canvas.stroke();

					// Beginning circle
					canvas.beginPath()
					canvas.arc(line_from_x, line_from_y, 5, 1.35, -1.35, false)
					canvas.fill()
					canvas.stroke()

					// Ending circle
					canvas.beginPath()
					canvas.arc(line_to_x, line_to_y, 5, 1.35, -1.35, false)
					canvas.fill()
					canvas.stroke()
				} else if (conflict_status == "enabled") {
					var line_from_x = $("#"+conflict).offset().left + $("#"+conflict).outerWidth()
					var line_from_y = $("#"+conflict).offset().top + 12
					var line_to_x   = $(this).offset().left + $(this).outerWidth()
					var line_to_y   = $(this).offset().top + 12
					var median = line_from_y + ((line_to_y - line_from_y) / 2)
					var curve = line_from_x + 25

					// Line
					canvas.beginPath();
					canvas.moveTo(line_from_x, line_from_y)
					canvas.quadraticCurveTo(curve, median, line_to_x, line_to_y);
					canvas.stroke();

					// Beginning circle
					canvas.beginPath()
					canvas.arc(line_from_x, line_from_y, 5, -1.75, 1.75, false)
					canvas.fill()
					canvas.stroke()

					// Ending circle
					canvas.beginPath()
					canvas.arc(line_to_x, line_to_y, 5, -1.75, 1.75, false)
					canvas.fill()
					canvas.stroke()
				}

				conflict_displayed[conflict+" :: "+$(this).attr("id")] = true
			}
		})

		return true
	},
	draw_dependencies: function() {
		if (!$(".extend li.depends").size() && !($.browser.safari || $.browser.opera || ($.browser.mozilla && $.browser.version >= 1.9)))
			return false

		$("#depends_canvas").remove()

		$(document.createElement("canvas")).attr("id", "depends_canvas").prependTo("body")
		$("#depends_canvas").css({
			position: "absolute",
			top: 0,
			bottom: 0,
			zIndex: 1
		}).attr({ width: $(document).width(), height: $(document).height() })

		var canvas = document.getElementById("depends_canvas").getContext("2d")
		var dependency_displayed = []

		$(".extend li.depends").each(function(){
			var classes = $(this).attr("class").split(" ")
			classes.shift() // Remove the module's safename class

			classes.remove(["conflict",
			                "depends",
			                "missing_dependency",
			                /depended_by_(.+)/,
			                /needs_(.+)/,
			                /conflict_(.+)/,
			                /ui-draggable(-dragging)?/])

			var gradients = []

			for (i = 0; i < classes.length; i++) {
				var conflict = classes[i].replace("depends_", "module_")

				if (dependency_displayed[$(this).attr("id")+" :: "+conflict])
					continue;

				canvas.fillStyle = "#e4e3fb"
				canvas.lineWidth = 3

				var this_status = $(this).parent().parent().attr("class").split(" ")[0] + "d"
				var conflict_status = $("#"+conflict).parent().parent().attr("class").split(" ")[0] + "d"

				if (conflict_status != this_status) {
					var line_from_x = (conflict_status == "disabled") ? $("#"+conflict).offset().left : $("#"+conflict).offset().left + $("#"+conflict).outerWidth()
					var line_from_y = $("#"+conflict).offset().top + 12

					var line_to_x = (conflict_status == "enabled") ? $(this).offset().left : $(this).offset().left + $(this).outerWidth()
					var line_to_y   = $(this).offset().top + 12
					var height = line_to_y - line_from_y
					var width = line_to_x - line_from_x

					if (height <= 45)
						gradients[i] = canvas.createLinearGradient(line_from_x, 0, line_from_x + width, 0)
					else
						gradients[i] = canvas.createLinearGradient(0, line_from_y, 0, line_from_y + height)

					gradients[i].addColorStop(0, '#0052cc');
					gradients[i].addColorStop(1, '#0096ff');

					canvas.strokeStyle = gradients[i]

					// Line
					canvas.moveTo(line_from_x, line_from_y)
					canvas.lineTo(line_to_x, line_to_y)
					canvas.stroke()

					// Beginning circle
					canvas.beginPath()
					if (conflict_status == "disabled")
						canvas.arc(line_from_x, line_from_y, 5, 1.35, -1.35, false)
					else
						canvas.arc(line_from_x, line_from_y, 5, -1.35, 1.35, false)
					canvas.fill()
					canvas.stroke()

					// Ending circle
					canvas.beginPath()
					if (conflict_status == "disabled")
						canvas.arc(line_to_x, line_to_y, 5, -1.75, 1.75, false)
					else
						canvas.arc(line_to_x, line_to_y, 5, 1.75, -1.75, false)
					canvas.fill()
					canvas.stroke()
				} else if (conflict_status == "disabled") {
					var line_from_x = $("#"+conflict).offset().left + $("#"+conflict).outerWidth()
					var line_from_y = $("#"+conflict).offset().top + 12
					var line_to_x   = $(this).offset().left + $(this).outerWidth()
					var line_to_y   = $(this).offset().top + 12
					var median = line_from_y + ((line_to_y - line_from_y) / 2)
					var height = line_to_y - line_from_y
					var curve = line_from_x + 25

					gradients[i] = canvas.createLinearGradient(0, line_from_y, 0, line_from_y + height);
					gradients[i].addColorStop(0, '#0052cc');
					gradients[i].addColorStop(1, '#0096ff');

					canvas.strokeStyle = gradients[i]

					// Line
					canvas.beginPath();
					canvas.moveTo(line_from_x, line_from_y)
					canvas.quadraticCurveTo(curve, median, line_to_x, line_to_y);
					canvas.stroke();

					// Beginning circle
					canvas.beginPath()
					canvas.arc(line_from_x, line_from_y, 5, -1.75, 1.75, false)
					canvas.fill()
					canvas.stroke()

					// Ending circle
					canvas.beginPath()
					canvas.arc(line_to_x, line_to_y, 5, -1.75, 1.75, false)
					canvas.fill()
					canvas.stroke()
				} else if (conflict_status == "enabled") {
					var line_from_x = $("#"+conflict).offset().left
					var line_from_y = $("#"+conflict).offset().top + 12
					var line_to_x   = $(this).offset().left
					var line_to_y   = $(this).offset().top + 12
					var median = line_from_y + ((line_to_y - line_from_y) / 2)
					var height = line_to_y - line_from_y
					var curve = line_from_x - 25

					gradients[i] = canvas.createLinearGradient(0, line_from_y, 0, line_from_y + height);
					gradients[i].addColorStop(0, '#0052cc');
					gradients[i].addColorStop(1, '#0096ff');

					canvas.strokeStyle = gradients[i]

					// Line
					canvas.beginPath();
					canvas.moveTo(line_from_x, line_from_y)
					canvas.quadraticCurveTo(curve, median, line_to_x, line_to_y);
					canvas.stroke();

					// Beginning circle
					canvas.beginPath()
					canvas.arc(line_from_x, line_from_y, 5, 1.35, -1.35, false)
					canvas.fill()
					canvas.stroke()

					// Ending circle
					canvas.beginPath()
					canvas.arc(line_to_x, line_to_y, 5, 1.35, -1.35, false)
					canvas.fill()
					canvas.stroke()
				}

				dependency_displayed[conflict+" :: "+$(this).attr("id")] = true
			}
		})

		return true
	}
}

// "Loading..." overlay.
$.fn.loader = function(remove) {
	if (remove) {
		$(this).next().remove()
		return this
	}

	var offset = $(this).offset()
	var loading_top = ($(this).outerHeight() / 2) - 11
	var loading_left = ($(this).outerWidth() / 2) - 63

	$(this).after("<div class=\"load_overlay\"><img src=\"<?php echo $config->chyrp_url; ?>/includes/close.png\" style=\"display: none\" class=\"close\" /><img src=\"<?php echo $config->chyrp_url; ?>/includes/loading.gif\" style=\"display: none\" class=\"loading\" /></div>")

	$(".load_overlay .loading").css({
		position: "absolute",
		top: loading_top+"px",
		left: loading_left+"px",
		display: "inline"
	})

	$(".load_overlay .close").css({
		position: "absolute",
		top: "3px",
		right: "3px",
		color: "#fff",
		cursor: "pointer",
		display: "inline"
	}).click(function(){ $(this).parent().remove() })

	$(".load_overlay").css({
		position: "absolute",
		top: offset.top,
		left: offset.left,
		zIndex: 100,
		width: $(this).outerWidth(),
		height: $(this).outerHeight(),
		background: ($.browser.msie) ? "transparent" : "transparent url('<?php echo $config->chyrp_url; ?>/includes/trans.png')",
		textAlign: "center",
		filter: ($.browser.msie) ? "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=scale, src='<?php echo $config->chyrp_url; ?>/includes/trans.png');" : ""
	})

	return this
}

// Originally from http://livepipe.net/extra/cookie
var Cookie = {
	set: function (name, value, days) {
		if (days) {
			var d = new Date();
			d.setTime(d.getTime() + (days * 1000 * 60 * 60 * 24));
			var expiry = "; expires=" + d.toGMTString();
		} else
			var expiry = "";

		document.cookie = name + "=" + value + expiry + "; path=/";
	},
	get: function(name){
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];

			while(c.charAt(0) == " ")
				c = c.substring(1,c.length);

			if(c.indexOf(nameEQ) == 0)
				return c.substring(nameEQ.length,c.length);
		}
		return null;
	},
	destroy: function(name){
		Cookie.set(name, "", -1);
	}
}

// Used to check if AJAX responses are errors.
function isError(text) {
	return /HEY_JAVASCRIPT_THIS_IS_AN_ERROR_JUST_SO_YOU_KNOW/m.test(text);
}

<?php $trigger->call("admin_javascript"); ?>
<!-- --></script>
