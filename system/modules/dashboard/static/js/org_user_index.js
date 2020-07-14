/**
 * Dashboard/user/index
 */

$(document).ready(function() {
    $("#edit_corporation").on("click", function() {
        location.href = Ibos.app.url("dashboard/unit/index");
        return false;
    });

    // 接
    if (U.getCookie('hooksyncuser') == '1') {
        parent.Ui.openFrame(U.getCookie('syncurl'), {
            title: U.lang("ORG.SYNC_USER"),
            cancel: true
        });
        U.setCookie('hooksyncuser', '');
        U.setCookie('syncurl', '');
    }
    //搜索
    $("#mn_search").search();

    //初始化上传
    // Ibos.upload.attach({
    //     formData: { module: 'dashboard' },
    //     accept: {
    //         title: 'Images',
    //         extensions: 'xls,xlsx',
    //         mimeTypes: 'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    //     },
    //     fileNumLimit: 1,
    //     custom_settings: {
    //         containerId: "file_target",
    //         inputId: "attachmentid"
    //     }
    // });

    var importUser = {
        op: {
            // 获取可登录用户数等信息
            "getUserInfo": function() {
                var url = Ibos.app.url("dashboard/user/getavailable");
                return $.get(url, $.noop, "json");
            }
        },
        // 更新可登录用户数等信息
        "updateUserInfo": function($wrap) {
            var _this = this,
                $currentNum = $wrap.find(".current-num"),
                $remainNum = $wrap.find(".remain-num");
            _this.op.getUserInfo().done(function(res) {
                if (res.isSuccess) {
                    $currentNum.text(res.current);
                    $remainNum.text(res.remain);
                } else {
                    Ui.tip(res.msg, "danger");
                }
            });
        }
    };

    Ibos.evt.add({
        "setUserStatus": function(param, elem) {
            var uid = U.getCheckedValue("user");
            if (!uid) {
                Ui.tip(U.lang("SELECT_AT_LEAST_ONE_ITEM"), "warning");
                return false;
            }
            $("#org_user_table").waiting(null, "normal");
            $.get(Ibos.app.url('dashboard/user/edit'), { op: param.op, uid: uid }, function(res) {
                $("#org_user_table").waiting(false);
                if (res.isSuccess) {
                    Ui.tip(Ibos.l("OPERATION_SUCCESS"));
                    userTable.draw();
                } else {
                    Ui.tip(res.msg, "danger");
                }
            }, 'json');
        },
        "exportUser": function() {
            var uid = U.getCheckedValue("user");
            if (!uid) {
                Ui.tip(U.lang("SELECT_AT_LEAST_ONE_ITEM"), "warning");
                return false;
            }
            window.location.href = Ibos.app.url('dashboard/user/export', { uid: encodeURI(uid) });
        },
        "batchImport": function(param, elem) {
            var dialog = new importData({ lock: false, tpl: 'user', module: 'user', per: 500 });

            $(document).on('closed.dialog', function(e) {
                setTimeout(function() {
                    userTable ? userTable.draw() : window.location.reload();
                }, 500);
            });
        },
        "closeDialog": function(param, elem) {
            var dialog = Ui.dialog.get("import_dialog");
            dialog.close();
            window.location.reload();
        },
        "againImport": function(param, elem) {
            var type = param.type,
                dialog = Ui.dialog.get("import_dialog"),
                $wrap = $("#batch_import_wrap");
            dialog.DOM.title.html(Ibos.l("ORG.BATCH_IMPORT_USER"));
            if (type == "success") {
                $("#batch_result_wrap").hide();
                $("#download_error_info").attr("href", "");
                importUser.updateUserInfo($wrap);
            } else {
                var $wrap = $("#batch_falure_wrap");
                $wrap.hide();
            }
            $("#batch_import_wrap").show();
            $("#attachmentid").val("");
            $("#file_target").children().remove();
        },
        "importExel": function(param, elem) {
            var dialog = Ui.dialog.get("import_dialog"),
                attachmentid = $("#attachmentid").val(),
                $importwrap = $("#batch_import_wrap"),
                $wrap = $("#upload_wrap");
            if (attachmentid) {
                var param = { aid: attachmentid },
                    url = Ibos.app.url('dashboard/user/import', { 'op': 'import' });
                $wrap.waiting(null, "mini", "normal");
                $.post(url, param, function(res) {
                    dialog.DOM.title.html(Ibos.l("ORG.BATCH_IMPORT_RESULT"));
                    if (res.isSuccess) {
                        var url = res.url,
                            success = res.successCount,
                            failure = res.errorCount;
                        $wrap.waiting(false);
                        $('#download_error_info').toggle(!!failure).attr("href", url);
                        $('#download_error_tip').toggle(!!failure);
                        $("#batch_result_wrap").show();
                        $importwrap.hide();
                        $("#import_success").text(success);
                        $("#import_failure").text(failure);
                    } else {
                        var $falureWrap = $("#batch_falure_wrap"),
                            $tip = $falureWrap.find(".info-wrap"),
                            $link = $falureWrap.find(".website-address");
                        $wrap.waiting(false);
                        $importwrap.hide();
                        $tip.text(res.msg);
                        $link.attr("href", res.url).text(res.url);
                        $falureWrap.show();
                    }
                });
            } else {
                Ui.tip(Ibos.l("ORG.SELECT_IMPORT_FILE"), "warning");
            }
        },
        // 查看上下级关系
        "checkRelationship": function(param, elem) {
            var url = Ibos.app.url('dashboard/user/relation');
            var dialog = Ui.dialog({
                title: Ibos.l("ORG.VIEW_SUBORDINATE_RELATIONSHIP"),
                id: "r_dialog",
                padding: 0,
                lock: true,
                width: "560px"
            });
            $.get(url)
                .done(function(res) {
                    dialog.content(res.html);
                });
        },
        "updateUserInfo": function(param, elem) {
            deptAndposOrg.init();

            var uid = U.getCheckedValue("user"),
                deptTree = $.fn.zTree.getZTreeObj('dept_tree'),
                posTree = $.fn.zTree.getZTreeObj('pos_tree'),
                URL = {
                    dept: Ibos.app.url("dashboard/department/batchalteruserdept"),
                    pos: Ibos.app.url("dashboard/position/batchalteruserpos")
                };

            if (!uid) {
                Ui.tip(Ibos.l("ORG.PLEASE_SELECT_UPDATE_USERS"), 'warning');
                return false;
            }

            // 清空原有勾选
            $.each([deptTree, posTree], function(idx, tree) {
                $.each(tree.getCheckedNodes(), function(i, v) {
                    tree.checkNode(v, false);
                });
            });

            uid = uid.split(",").map(function(val) {
                return "u_" + val;
            });

            var dialog = Ui.dialog({
                title: Ibos.l("ORG.UPDATE_USER_POSITION_OR_DEPARTMENT_INFO"),
                id: "update_dialog",
                padding: 0,
                margin: 0,
                width: 350,
                lock: false,
                content: document.getElementById("update_userinfo_dialog"),
                ok: function() {
                    var deptid = $('input[name=deptid]').val(),
                        posid = $('input[name=posid]').val();

                    deptid && $.post(URL.dept, { member: uid, id: deptid }, function(res) {
                        if (res.isSuccess) {
                            Ui.tip(Ibos.l("ORG.UPDATE_DEPARTMENT_SUCCESS"));
                            userTable.draw();
                        } else {
                            Ui.tip(res.msg, "warning");
                        }
                    });

                    posid && $.post(URL.pos, { member: uid, id: posid }, function(res) {
                        if (res.isSuccess) {
                            Ui.tip(Ibos.l("ORG.UPDATE_POSTION_SUCCESS"));
                            userTable.draw();
                        } else {
                            Ui.tip(res.msg, "warning");
                        }
                    });
                }
            });
        },
        "getStatusList": function(param, elem) {
            var $elem = $(elem),
                $parent = $elem.parent(),
                $currStatusbtn = $elem.closest('.btn-group').find('.btn'),
                type = $elem.data('type'),
                currStatusText = $elem.text();

            $parent.children().removeClass('active');
            $elem.addClass('active');
            $currStatusbtn.html(currStatusText + '<i class="caret"></i>')

            userTable.user.type = type;
            userTable.user.search();
        }
    });

    var canwrite = Ibos.app.g('canwrite', 1);

    var ztreeOpt = (canwrite == 0) ? {
        "nodeOnClick": function(event, treeId, treeNode) {
            userTable.user.deptid = treeNode.id.substr(2);
            $('#corp_unit').removeClass('curSelectedNode');
            userTable.user.search();
        },
    } : {
        "addDiyDom": function(treeId, treeNode) {
            var aObj = $("#" + treeNode.tId + "_a");
            var optBtn = "<span class='utree-opt-wrap'>" +
                "<a href='" + Ibos.app.url('dashboard/department/edit', { 'op': 'get', 'id': treeNode.id.substr(2) }) + "' title='" + Ibos.l("ORG.EDIT_DEPARTMENT_INFO") + "' class='o-org-ztree-edit opt-btn opt-edit-btn'></a>" +
                "<a href='javascript:;' title='" + Ibos.l("ORG.DELETE_DEPARTMENT_TIP") + "' class='o-org-ztree-del opt-btn opt-del-btn' data-action='delZtreeNode' data-deptname='" + treeNode.text + "' id='" + treeNode.id.substr(2) + "'></a>" +
                "</span>";

            aObj.append(optBtn);

            //绑定删除节点操作
            $("#utree").on("click", ".opt-del-btn", function(evt) {
                var $tree = $("#utree"),
                    treeObj = $.fn.zTree.getZTreeObj("utree"),
                    $this = $(this),
                    id = $.attr(this, "id"),
                    name = $this.attr("data-deptname");
                Ui.confirm(Ibos.l("ORG.SURE_DELETE_DEPARTMENT", { name: name }), function() {
                    var node = treeObj.getNodesByParamFuzzy("id", id, null),
                        param = { id: id },
                        url = Ibos.app.url('dashboard/department/del');
                    $tree.waiting(null, 'mini', 'normal');
                    $.post(url, param, function(res) {
                        if (res.isSuccess) {
                            treeObj.removeNode(node[0]);
                            Ui.tip(res.msg);
                            $tree.waiting(false);
                        } else {
                            Ui.tip(res.msg, "danger");
                            $tree.waiting(false);
                        }
                    });
                });
                evt.stopPropagation();
            });

            //阻止点击编辑跳转时的冒泡事件
            $("#utree").on("click", ".opt-edit-btn", function(evt) {
                evt.stopPropagation();
            });
        },
        "zTreeOnDrop": function(event, treeId, treeNodes, targetNode, moveType) {
            var node = treeNodes[0],
                tid = node.tId,
                index = $("#" + tid).index(),
                id = node.id,
                pid;
            if (moveType == "inner") {
                pid = targetNode ? targetNode.id : 0;
            } else {
                pid = targetNode ? targetNode.pid : 0;
            }
            var param = { id: id, pid: pid, index: index },
                url = Ibos.app.url('dashboard/department/edit', { 'op': 'structure' });
            $.post(url, param, function(res) {
                if (res.isSuccess) {
                    Ui.tip(Ibos.l("OPERATION_SUCCESS"));
                } else {
                    Ui.tip(Ibos.l("OPERATION_FAILED"), "danger");
                    window.location.reload();
                }
            });
        },
        "nodeOnClick": function(event, treeId, treeNode) {
            userTable.user.deptid = treeNode.id.substr(2);
            $('#corp_unit').removeClass('curSelectedNode');
            userTable.user.search();
        },
        "getFontCss": function(treeId, treeNode) {
            return (!!treeNode.highlight) ? { "font-weight": "700" } : { "font-weight": "normal" };
        },
        "selectAuxiliaryNode": function(array) {
            var treeObj = $.fn.zTree.getZTreeObj("utree");
            for (var i = 0; i < array.length; i++) {
                var node = treeObj.getNodesByParam("id", array[i], null);
                if (node.length) {
                    node[0].highlight = true;
                    treeObj.updateNode(node[0]);
                }
            }
        }
    };

    var userTable = (function() {
        var table = $('#org_user_table').DataTable($.extend({}, Ibos.settings.dataTable, {
            deferLoading: 0,
            ajax: {
                url: Ibos.app.url('dashboard/user/getuserlist'),
                type: 'post',
                dataSrc: function(res) {
                    if (res.isSuccess) {
                        return res.data;
                    } else {
                        Ui.tip(res.msg, 'warning');
                        return [];
                    }
                }
            },
            initComplete: function() {
                $(this).find('[data-name]').label();
            },
            rowCallback: function(row, data) {
                $(row).find("label input[type='checkbox']").label();
            },
            order: [],
            columns: [
                //复选框
                {
                    "data": "",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return (row.uid) !== 1 ? '<label class="checkbox"><input type="checkbox" name="user" value="' + row.uid + '"/></label>' : '';
                    }
                },
                //头像
                {
                    "data": "",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return '<div class="avatar-box" data-param="uid=' + row.uid + '">' +
                            '<span class="avatar-circle">' +
                            '<img src="' + row.avatar_small + '" title="' + row.realname + '"/>' +
                            '</span>' +
                            '</div>';
                    }
                },
                //姓名
                {
                    "data": "realname",
                    "orderable": false,
                    "render": function(data, type, row) {
                        var realname = row.realname,
                            posname = row.posname ? row.posname : '-';
                        return '<div class="xcm">' + realname + '</div>' +
                            '<div class="fss">' + posname + '</div>';
                    }
                },
                //部门
                {
                    "data": "deptname",
                    "orderable": false,
                    "render": function(data, type, row) {
                        var text = row.deptname ? row.deptname : '-';
                        return '<span class="fss">' + text + '</span>';
                    }
                },
                //角色
                {
                    "data": "rolename",
                    "orderable": false,
                    "render": function(data, type, row) {
                        var i, len,
                            rolename = row.rolename ? row.rolename : ' ',
                            text = '<span class="fss xcr">' + rolename + '</span>';
                        if (len = row.relatedRole.length) { // array
                            for (i = 0; i < len; i++) {
                                text += '<span class="fss">，' + row.relatedRole[i] + '</span>';
                            }
                        }
                        // text = text.substring(0,text.length-1);
                        return text;
                    }
                },
                 //微信号
                {
                    "data": "status",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return '<span class="fss">' + (row.status == 0 ? '在职' : (row.status == 1 ? '锁定' : '禁用')) + '</span>';
                    }
                },
                //手机
                {
                    "data": "mobile",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return '<span class="fss">' + (row.mobile) ? row.mobile : ' ' + '</span>';
                    }
                },
                //操作
                {
                    "data": "",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return (canwrite == 1) ? '<a href="' + Ibos.app.url('dashboard/user/edit', { uid: row.uid }) + '" class="cbtn o-edit"></a>' : '';
                    }
                }
            ]
        }));

        return table;
    })();

    userTable.draw();
    userTable.user = {
        deptid: 0,
        type: 'enabled',
        search: function() {
            var that = this,
                url = Ibos.app.url('dashboard/user/getuserlist', {
                    type: that.type,
                    deptid: that.deptid
                });

            userTable.ajax.url(url).load();
        }
    };

    $('#corp_unit').on('click', function(e) {
        var $this = $(this),
            treeObj = $.fn.zTree.getZTreeObj("utree"),
            checked = treeObj.getSelectedNodes();

        $.each(checked, function(i, e) {
            treeObj.cancelSelectedNode(e);
        });

        $this.addClass('curSelectedNode');
        userTable.user.deptid = 0;
        userTable.user.search();
    });

    $('#mn_search').search(function(val) {
        userTable.search(val).draw();
    });

    // 初始化右栏树
    var settings = {
        data: {
            key: {
                name: 'text'
            },
            simpleData: {
                enable: true,
                pIdKey: 'pid'
            }
        },
        view: {
            showLine: false,
            selectedMulti: false,
            showIcon: false,
            addDiyDom: ztreeOpt.addDiyDom,
            fontCss: ztreeOpt.getFontCss
        },
        edit: {
            enable: true,
            drag: {
                isCopy: false,
                isMove: true
            }
        },
        callback: {
            onDrop: ztreeOpt.zTreeOnDrop,
            onClick: ztreeOpt.nodeOnClick
        }
    };
    var $tree = $("#utree");
    $tree.waiting(null, 'mini');
    $.fn.zTree.init($tree, settings, Ibos.data.converToArray(Ibos.data.get('department', function(data) {
        return data.id !== 'c_0';
    })));
    ztreeOpt.selectAuxiliaryNode && ztreeOpt.selectAuxiliaryNode(Ibos.app.g("auxiliaryId"));
    $tree.waiting(false);
    /**
     *  批量更新用户部门和岗位信息
     *
     */
    var deptAndposOrg = (function() {
        var $dept_box = $('#dept_tree'),
            $pos_box = $('#pos_tree'),
            TreeSetting;

        var treeCheckBefore, treeCheck, init;

        treeCheckBefore = function(treeId, node) {
            var $ztree = $.fn.zTree.getZTreeObj(treeId),
                checkeds = $ztree.getCheckedNodes();

            if (!node.checked && checkeds.length > 0) {
                checkeds[0].checked = false;
                $ztree.updateNode(checkeds[0]);
            }
        };

        treeCheck = function(evt, treeId, node) {
            var $dom;

            $dom = /^dept/.test(treeId) ? $('input[name=deptid]') : $('input[name=posid]');
            $dom.val(node.id);
        };

        TreeSetting = {
            data: {
                key: {
                    name: 'text'
                },
                simpleData: {
                    enable: true,
                    pIdKey: 'pid'
                }
            },
            check: {
                enable: true,
                chkboxType: { Y: '', N: '' }
            },
            view: {
                showLine: false,
                selectedMulti: false,
                showIcon: false,
            },
            callback: {
                beforeCheck: treeCheckBefore,
                onCheck: treeCheck
            }
        };

        init = function() {
            $dept_box.waiting(null, 'mini');
            $.fn.zTree.init($dept_box, TreeSetting, Ibos.data.converToArray(Ibos.data.get('department', function(data) {
                delete data.isParent;
                return true;
            })));
            $.fn.zTree.init($pos_box, TreeSetting, Ibos.data.converToArray(Ibos.data.get('position', 'positioncategory', function(data) {
                delete data.isParent;
                return true;
            })));
            $dept_box.waiting(false);
        };

        return { init: init };
    })();
});
