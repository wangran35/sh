(function() {
	// 图章上传及背景图上传

	var PicUpload = {
		create: function($el, settings){
			var that = this,
				id = '#' + $el.attr("id"),
				uploadObj;
			
			// 找到SWFUpload控件的替换ID并加入settings中
			settings.pick = id;
			// 此属性用于缓存SWFUpload替代节点的父节点，以便后续调用
			this[id] = Ibos.upload.image(settings);
		},
		init: function($els, settings){
			var that = this;
			$els.each(function(){
				var $el = $(this);
				that.create($el, settings);
			})
		},
		remove: function(id, callback){
			this['#'+ id].destroy();	
			callback && callback();
		}
	};
	
	//Init, Add, Create, Delete
	// 配置
	var picUploadSettings = {
		server: Ibos.app.url("dashboard/login/index", { "op" : "upload"}),
		fileVal: "bg",
		custom_settings: {
			success: function(data, file) {
				var id = this.movieName,
						$holderWrap = $(this.options.pick),
						$img = $holderWrap.parent().parent().siblings("img"),
						$item = $img.parent();
				// 移除对应样式
				$item.removeClass("pic-item-new").find(".o-img-default-large").attr("class", "o-img-default");
				// 调整控件宽高
				// $("#" + id).attr("width", 140).attr("height", 120);
				$img.attr("src", file.url);
				$($img.next('input[type=hidden]').get(0)).val(file.fakeUrl);
			}
		}
	};

	var picSelector = ".pic-upload-holder div", 
		picList = $("#pic_list"), 
		picUploadHolders = picList.find(picSelector);
	// Init
	PicUpload.init(picUploadHolders, picUploadSettings);
	// Add
	var picUploadAdd = function(data, lastItem) {
		var tpl = $.template("pic_upload_tpl", data), $node = $(tpl), $holder = $node.find(picSelector);
		$node.find("[data-toggle='switch']").iSwitch("");
		$node.insertBefore(lastItem);
		PicUpload.create($holder, $.extend({}, picUploadSettings, {
			button_width: 320,
			button_height: 170
		}));
	};
	$("#pic_upload_add").on("click", function() {
		var lastItem = $(this).parent(),
			// 用于模板替换的键值集
			data = {picid: +new Date()};
		picUploadAdd(data, lastItem);
	});
	//Remove
	picList.on("click", ".o-trash", function() {
		var $el = $(this), picId = $el.data("target"), id = $el.data('id'), removeIdObj = $('#removeId');
		if (id) {
			var removeId = removeIdObj.val(), removeIdSplit = removeId.split(',');
			removeIdSplit.push(id);
			removeIdObj.val(removeIdSplit.join());
		}
		PicUpload.remove(picId, function() {
			$el.parents("li").first().remove();
		});
	});
})();
