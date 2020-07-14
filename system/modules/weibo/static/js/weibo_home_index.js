/**
 * 微博主页
 * 2014-01-06
 * @author inaki
 */
var HomeIndex = HomeIndex || {};

/**
 * 图片上传
 * @method picUpload
 */
HomeIndex.picUpload = function() {
    // 上传框
    var $picBox = $('[data-node-type="picBox"]');
    /**
     * 上传开始
     * @method _uploadStart
     * @param  {Object} file 上传文件
     */
    var _uploadStart = function(file) {
        // 移除上张图片预览
        $picBox.find(".wb-upload-preview").remove();
        // showModal
        $('<div class="wb-upload-modal" data-node-type="uploadModal"></div>')
            .appendTo($picBox).fadeTo(1000, 0.5);
        // showProgress
        var tmpl = '<div class="wb-upload-progress" data-node-type="uploadProgress">' +
            '<div data-node-type="uploadProgressbar"></div> ' +
            '<span>上传中 <em data-node-type="uploadPercent">0%</em></span>' +
            '</div>';
        $(tmpl).appendTo($picBox).find('[data-node-type="progressbar"]').progressbar();
    };
    /**
     * 上传过程
     * @method _uploadProgress
     * @param  {Object} file     上传文件
     * @param  {Number} uploaded 上传进度
     * @param  {Number} total    上传总量
     */
    var _uploadProgress = function (file, percent) {
        var $progressbar = $picBox.find('[data-node-type="uploadProgressbar"]'),
            $percent = $picBox.find('[data-node-type="uploadPercent"]');

        percent = Math.floor(percent * 100);
        // updateProgress
        $progressbar.progressbar("val", percent);
        $percent.html(percent + "%");
    };
    /**
     * 上传成功
     * @method _uploadSuccess
     * @param  {Object} file 上传文件
     * @param  {Object} res  文件返回的类型
     */
    var _uploadSuccess = function(file, res) {
        var $tip = $picBox.find('.wb-upload-success-tip'),
            resData = res;
        // hideProgress
        $picBox.find('[data-node-type="uploadProgress"]').remove();
        // tip
        $tip.fadeIn();
        // hideTip, hideModal
        $tip.fadeOut();
        $picBox.find('[data-node-type="uploadModal"]').fadeOut(function() {
            $(this).remove();
        });
        // showPreview

        $picBox.addClass("wb-upload-success");
        $.tmpl("showPreview_tpl", {
            attachUrl: resData.url,
            attachName: resData.name
        }).hide().appendTo($picBox).fadeIn(1000);
        // setValue
        $picBox.find('[data-node-type="picId"]').val(resData.aid);
    };

    Ibos.evt.add({
        "removePic": function() {
            $picBox.find(".wb-upload-preview").remove();
            $picBox.removeClass("wb-upload-success").find('[data-node-type="picId"]').val("");
        }
    });

    Ibos.upload.image({
        pick: "#wb_imgupload",
        formData: { module: 'weibo' },
        fileQueued: _uploadStart,
        uploadProgress: _uploadProgress,
        uploadSuccess: _uploadSuccess
    });
};

$(function() {
    // 右侧栏定位
    (function() {
        var $sb = $("#wb_sidebar"),
            $mn = $("#wb_home_mainer");
        $sb.affixTo($mn);
    })();

    // 图片上传
    HomeIndex.picUpload();

    // 微博最大字数
    var WBNUMS = Ibos.app.g('wbnums') || 200;
    // 发布框
    $('[data-node-type="textarea"]').on("countchange", function(evt, data) {
        var isSendable = 0 < data.count && data.count <= WBNUMS,
            $btn = $(this).closest("[data-node-type='publishWrap']").find('[data-node-type="publishBtn"]');
        $btn.toggleClass("btn-warning", isSendable).prop("disabled", !isSendable);
    }).charCount({ style: "wb-word-limit", max: WBNUMS });


    //初始化表情功能
    $('#wb_face').ibosEmotion({
        target: $('#wb_pub_textarea')
    });
    $("#mn_search").search(function(val) {
        window.location.href = Ibos.app.url('weibo/home/index', { feedkey: val, type: Ibos.app.g("type"), feedtype: Ibos.app.g("feedtype") });
    });

    // 发布类型
    var publishPicType = false;
    // 发布范围，全公司
    var publishRange = 0;
    var CUSTOM = 3;
    Ibos.evt.add({
        // 发布
        "publish": function(param, elem) {
            var $elem = $(elem),
                $wrap = $elem.closest("[data-node-type='publishWrap']"),
                $txt = $wrap.find('[data-node-type="textarea"]'),
                interval = Ibos.app.g("submitInterval") || 0,
                txt = $txt.val();

            if (!$elem.data("disabledSubmit")) {
                if ($.trim(txt) !== "") {
                    // 替换特殊字符
                    param.body = U.entity.escape(txt);
                    param.view = publishRange;
                    //  当可见范围为指定人员时
                    if (publishRange == CUSTOM) {
                        param.viewid = $wrap.find('[data-node-type="publishRange"]').val();
                        if (param.viewid == '') {
                            Ui.tip('@WB.SPECIFY_FEED_USER', 'warning');
                            return false;
                        }
                    }
                    $elem.button("loading");
                    // 当有附件时
                    if (publishPicType) {
                        var attachid = $wrap.find('[data-node-type="picId"]').val();
                        if (attachid) {
                            param.type = 'postimage';
                            param.attachid = attachid;
                        } else {
                            param.type = "post";
                        }
                    } else {
                        param.type = 'post';
                    }
                    param.formhash = Ibos.app.g('formHash');
                    // 发布请求
                    Wb.publish(param).done(function(res) {
                        if (res.isSuccess) {
                            $('.no-data-tip').remove();
                            $txt.val("").trigger("focus");
                            $elem.button("reset");
                            // 图片类型发布后，移除图片并关闭图片面板
                            if (publishPicType) {
                                Ibos.evt.fire("removePic");
                                $('[data-action="pic"]').trigger("click");
                            }
                            // Tip
                            Ui.tip("@WB.PUBLISH_SUCCESS");
                            Ui.showCreditPrompt();
                            Wb.insertFeedBefore(res.data);
                            // 发布成功后，在一定的时间间隔后，才可再次发布
                            $elem.data("disabledSubmit", true);
                            setTimeout(function() {
                                $elem.removeData("disabledSubmit");
                            }, interval);
                        }
                    });
                }
            } else {
                Ui.tip(Ibos.l('WB.PUBLISH_TIME_LIMIT', { time: interval / 1000 }), 'warning');
            }
        },
        // 发布范围选择
        "publishTo": function(param, elem) {
            var $elem = $(elem),
                $btn = $elem.closest("ul").prev(),
                btnTextNode = $btn[0].childNodes[0];
            btnTextNode.nodeValue = param.text;
            Ui.selectOne($elem.parent());
            // 指定人可见
            var $publishRange = $elem.closest("[data-node-type='publishWrap']").find("[data-node-type='publishToRange']");
            if (param.type == CUSTOM) {
                // 出现人员选择框
                $publishRange.show().find('[data-node-type="publishRange"]').userSelect({
                    data: Ibos.data.get()
                });
            } else {
                $publishRange.hide();
            }
            publishRange = param.type;
        },
        // 图片内容
        "pic": function(param, elem) {
            var $elem = $(elem),
                $picBox = $elem.closest("[data-node-type='publishWrap']").find("[data-node-type='picBox']");
            $elem.toggleClass("active");
            publishPicType = !publishPicType;
            $picBox.toggle();
        },
        // 右栏快速关注
        "quickFollow": function(param, elem) {
            var $elem = $(elem),
                $followBox = $elem.closest("[data-node-type='quickFollowBox']");
            $elem.button("loading");
            // 这里一个请求，处理了关注和返回新推荐用户数据
            // 如果后台实现不方便可分成两次请求
            $.get("system/modules/weibo/views/t_newpush.php", param, function(res) {
                // 此处关注成功后，如果有其他的推荐用户，会替代节点
                if (res.isSuccess) {
                    $elem.button("reset").html(Ibos.l("FOLLOWED"));
                    if (res.data) {
                        $followBox.replaceWith(res.data);
                    } else {
                        $followBox.remove();
                    }
                }
            }, "json");
        }
    });
});
