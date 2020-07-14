<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/organization.css?<?php echo VERHASH; ?>">
<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/organization_role.css?<?php echo VERHASH; ?>">
<div class="ct">
    <div class="clearfix">
        <h1 class="mt">通讯录管理 > 岗位管理</h1>
    </div>
    <div>
        <!-- 部门信息 start -->
        <div class="ctb">
            <div>
                <div class="">
                    <form action="<?php echo $this->createUrl('position/add'); ?>" method="post" id="position_add_form"
                          class="form-horizontal user-info-form">
                        <div class="control-group">
                            <label class="control-label">
                                <span>排序序号</span>
                            </label>
                            <div class="controls">
                                <input type="text" name="sort" placeholder="请输入排序号" id="order_id"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">
                                <span>岗位名称</span>
                            </label>
                            <div class="controls">
                                <input type="text" name="posname" placeholder="请输岗位名称" id="pos_name"/>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">
                                <span>岗位分类</span>
                            </label>
                            <div class="controls">
                                <select name="catid">
                                    <?php echo $category; ?>
                                </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">
                            </label>
                            <div class="controls">
                                <button type="submit" class="btn btn-large btn-primary">提交</button>
                            </div>
                        </div>
                        <input type="hidden" name="posSubmit" value="1"/>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src='<?php echo STATICURL; ?>/js/lib/formValidator/formValidator.packaged.js?<?php echo VERHASH; ?>'></script>
<script src="<?php echo $assetUrl; ?>/js/db_position.js"></script>