<?php defined('C5_EXECUTE') or die('Access denied.');
$html = Loader::helper('html');
$json = Loader::helper('json');
$this->addHeaderItem($html->css('clear_files.css', 'files_cleaner'));
?>
<script type="text/javascript"><!--
function ClearFilesProvider(handle, name, noteIndex) {
	var noteMark, i, cName;
	this.handle = handle;
	this.name = name;
	this.noteIndex = noteIndex;
	this.Busy = false;
	$("#clearfiles_providers").append(this.row = $('<tr class="provider"></tr>'));
	this.row
		.append($('<td class="ico"></td>')
			.append($('<a class="ico-refresh" href="#" onclick="$(this).data(\'provider\').Refresh(); return false;"></a>')
				.attr("title", <?php echo $json->encode(t('Refresh')); ?>)
				.data("provider", this)
			)
			.append('<span class="ico-busy"></span>')
		)
		.append(cName = $('<td class="name"></td>')
			.text(this.name)
		)
		.append($('<td class="state"></td>')
			.append($('<span class="text-busy"></span>')
				.text(<?php echo $json->encode(t('Please wait')); ?>)
			)
			.append(this.stateLay = $('<span></span>'))
		)
		.append($('<td class="action"></td>')
			.append($('<a class="ico-delete" href="#" onclick="$(this).data(\'provider\').Delete(); return false;"></a>')
				.attr("title", <?php echo $json->encode(t('Delete files/directories')); ?>)
				.data("provider", this)
			)
			.append('<span class="ico-busy"></span>')
		)
	;
	noteMark = "";
	for(i = 0; i <= this.noteIndex; i++) {
		noteMark += "*";
	}
	if(noteMark.length) {
		cName.append($('<span class="noteIndex"></span>').text(noteMark));
	}
}
ClearFilesProvider.prototype = {
	setBusy: function(busy) {
		this.Busy = busy ? true : false;
		if(this.Busy) {
			this.row.addClass("working");
		} else {
			this.row.removeClass("working");
		}
	},
	Refresh: function() {
		var me;
		me = this;
		this.stateLay.empty().css("color", "");
		this.setBusy(true);
		$.ajax({
			url: <?php echo $json->encode($this->action('get_content')); ?>,
			async: true,
			cache: false,
			type: "POST",
			dataType: "json",
			data: {provider: this.handle},
			success: function(r) {
				var o;
				if(r == null) {
					r = <?php echo $json->encode(t('Empty result!')); ?>;
				}
				if(typeof(r) == "string") {
					me.stateLay.text(r).css("color", "red");
				}
				else {
					me.Data = r;
					if(r.dirs.length) {
						o = $('<a href="#" onclick="$(this).data(\'provider\').Show(\'dirs\');return false"></a>')
							.data('provider', me)
						;
					} else {
						o = $('<span></span>');
					}
					o.text(<?php echo $json->encode(htmlspecialchars(t('Directories: '))); ?> + r.dirs.length);
					me.stateLay.append(o);
					me.stateLay.append(', ');
					if(r.files.length) {
						o = $('<a href="#" onclick="$(this).data(\'provider\').Show(\'files\');return false"></a>')
							.data('provider', me)
						;
					} else {
						o = $('<span></span>');
					}
					o.text(<?php echo $json->encode(htmlspecialchars(t('Files: '))); ?> + r.files.length);
					me.stateLay.append(o);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				var s;
				if((errorThrown == "Bad Request") || (textStatus == "parsererror")) {
					s = jqXHR.responseText;
				}else if((typeof errorThrown == "string") && errorThrown.length) {
					s = errorThrown;
				} else {
					s = textStatus;
				}
				me.stateLay.text(s).css("color", "red");
			},
			complete: function() {
				me.setBusy(false);
			}
		});
	},
	Show: function(what) {
		var $lay, $l, h;
		h = Math.min(Math.max($(window).height() - 300, 200), 1000),
		$lay = $('<div></div>').append($('<div style="max-height:' + h + 'px;overflow:auto"></div>').append($('<table style="width:100%"></table>').append($l = $('<tbody></tbody>'))));
		$.each(this.Data[what], function() {
			var $n;
			$l.append($('<tr></tr>')
				.append($n = $('<td></td>').text(this.name))
				.append($('<td style="text-align:right"></td>').text(ClearFiles.SizeToString(this.size)))
			);
			if((typeof(this.comment) == "string") && this.comment.length) {
				$n.append($('<div style="font-size:70%;color:#777"></div>').text(this.comment));
			}
		});
		$(document.body).append($lay);
		$lay.dialog({
			title: {"dirs": <?php echo $json->encode(t('Directories')); ?>, "files": <?php echo $json->encode(t('Files')); ?>}[what],
			modal: true,
			width: 500,
			close: function()
			{
				$lay.remove();
			}
		});
	},
	Delete: function() {
		if(!confirm(<?php echo $json->encode(t('Are you sure you want to proceed?')); ?>)) {
			return;
		}
		var me, result = "?";
		me = this;
		this.stateLay.empty().css("color", "");
		this.setBusy(true);
		$.ajax({
			url: <?php echo $json->encode($this->action('do_clean')); ?>,
			async: true,
			cache: false,
			type: "POST",
			dataType: "json",
			data: {provider: this.handle},
			success: function(r) {
				var o;
				result = (r == null) ? <?php echo $json->encode(t('Empty result!')); ?> : r;
			},
			error: function(jqXHR, textStatus, errorThrown) {
				if((errorThrown == "Bad Request") || (textStatus == "parsererror")) {
					result = jqXHR.responseText;
				}else if((typeof errorThrown == "string") && errorThrown.length) {
					result = errorThrown;
				} else {
					result = textStatus;
				}
			},
			complete: function() {
				if(typeof(result) == "string") {
					alert(result);
				}
				me.setBusy(false);
				me.Refresh();				
			}
		});
	}
};
var ClearFiles = {
	Providers: [],
	RefreshAll: function() {
		$.each(ClearFiles.Providers, function() {
			this.Refresh();
		});
	},
	SizeToString: function(value) {
		var size, sign, fmt;
		fmt = function(n) {
			var i;
			n = Math.round(n * 100) / 100;
			n = n.toString();
			i = n.indexOf(".");
			if(i < 0) {
				n += ".00";
			} else {
				if(i == n.length - 2) {
					n += "0";
				}
			}
			return n;
		};
		if(typeof(value) == "string") {
			size = parseInt(value, 10);
		} else {
			size = value;
		}
		if((typeof(size) != "number") || isNaN(size)) {
			return value;
		}
		if(size < 0) {
			size = -size;
			sign = "-";
		} else {
			sign = "";
		}
		if(size <= 1000) {
			return sign + size + " B";
		}
		size = size / 1024;
		if(size < 1000) {
			return sign + fmt(size) + " KB";
		}
		size = size / 1024;
		if(size < 1000) {
			return sign + fmt(size) + " MB";
		}
		size = size / 1024;
		if(size < 1000) {
			return sign + fmt(size) + " GB";
		}
		size = size / 1024;
		return sign + fmt(size) + " TB";
	}
};
$(document).ready(function()
{
	var providers = <?php echo $json->encode($providers); ?>, notesMark, notes = [], noteIndex;
	ClearFiles.Providers = [];
	notesMark = "";
	$.each(providers, function() {
		noteIndex = -1;
		if((typeof(this.note) == "string") && this.note.length) {
			noteIndex = $.inArray(this.note, notes);
			if(noteIndex < 0) {
				notes[noteIndex = notes.length] = this.note;
				notesMark += "*";
				$("#clearfiles_notes")
					.append(
						$('<li></li>').text(notesMark + ": " + this.note)
					)
					.show()
				;
			}
		};
		ClearFiles.Providers.push(new ClearFilesProvider(this.handle, this.name, noteIndex));
	});
	ClearFiles.RefreshAll();
});
//--></script>

<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('Clear Files'), t('This page allows you to clear potentially unuseful files. **USE AT YOUR OWN RISK**'), 'span10 offset1'); ?> 
<table id="clearfiles_providers" class="table table-striped"><tbody></tbody></table>
<ul id="clearfiles_notes" style="display:none;list-style-type:none"></ul>
<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneFooterWrapper();
