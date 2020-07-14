<?php

use application\core\utils\Env;
use application\core\utils\Ibos;
use application\modules\main\utils\Main;

?>
<!-- load css -->
<link rel="stylesheet"
      href="<?php echo STATICURL; ?>/js/lib/dataTable/css/jquery.dataTables_ibos.min.css?<?php echo VERHASH; ?>">
<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/officialdoc.css">
<link rel="stylesheet" href="<?php echo STATICURL; ?>/css/emotion.css?<?php echo VERHASH; ?>">
<!-- load css end-->

<!-- Mainer -->
<div class="mc clearfix">
    <!-- Sidebar -->
    <?php echo $this->getSidebar($this->catId); ?>
    <!-- Sidebar end -->

    <!-- Mainer right -->
    <div class="mcr">
        <!-- Mainer nav -->
        <div class="officialdoc-nav-wrap">
            <ul class="mnv nl clearfix">
                <li class="active" data-action="typeSelect" data-type="all">
                    <a href="javascript:;">
                        <i class="o-art-all"></i>
                        全部
                    </a>
                </li>
                <li data-action="typeSelect" data-type="nosign">
                    <a href="javascript:;">
                        <i class="o-art-unsign"></i>
                        <?php echo $lang['No sign']; ?>
                        <?php if ($countNosign != 0): ?><span
                            class="bubble"><?php echo $countNosign; ?></span><?php endif; ?>
                    </a>
                </li>
                <li data-action="typeSelect" data-type="sign">
                    <a href="javascript:;">
                        <i class="o-art-sign"></i>
                        <?php echo $lang['Sign']; ?>
                    </a>
                </li>
                <li data-action="typeSelect" data-type="notallow">
                    <a href="javascript:;">
                        <i class="o-art-uncensored"></i>
                        <?php echo Ibos::lang('No verify'); ?>
                        <?php if ($countNotAllOw != 0): ?><span
                            class="bubble"><?php echo $countNotAllOw; ?></span><?php endif; ?>
                    </a>
                </li>
                <li data-action="typeSelect" data-type="draft">
                    <a href="javascript:;">
                        <i class="o-art-draft"></i>
                        <?php echo Ibos::lang('Draft'); ?>
                        <?php if ($countDraft != 0): ?><span
                            class="bubble"><?php echo $countDraft; ?></span><?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
        <div class="page-list" id="doc_base">
            <div class="page-list-header">
                <div class="btn-toolbar pull-left">
                    <button class="btn btn-primary pull-left"
                            onclick="location.href = '<?php echo $this->createUrl('officialdoc/add'); ?>&catid='+ (Ibos.local.get('catid') || 0);">
                        <?php echo Ibos::lang('New doc'); ?>
                    </button>
                    <div class="btn-group" id="doc_more" style="display:none;">
                        <button class="btn dropdown-toggle" data-toggle="dropdown">
                            <?php echo Ibos::lang('More Operating'); ?>
                            <i class="caret"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:;" data-action="moveDoc">
                                    <i class="o-menu-move"></i>
                                    <?php echo Ibos::lang('Move'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:;" data-action="topDoc">
                                    <i class="o-menu-top"></i>
                                    <?php echo Ibos::lang('Top'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:;" data-action="highlightDoc">
                                    <i class="o-menu-light"></i>
                                    <?php echo Ibos::lang('Highlight'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:;" data-action="removeDocs">
                                    <i class="o-menu-trash"></i>
                                    <?php echo Ibos::lang('Delete'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <form action="javascript:;" method="post">
                    <div class="search search-config pull-right span3">
                        <input type="text" placeholder="输入标题查询" name="keyword" id="mn_search" nofocus
                               <?php if (Env::getRequest('param')): ?>value="<?php echo Main::getCookie('keyword'); ?>"<?php endif; ?>>
                        <a href="javascript:;">search</a>
                        <input type="hidden" name="type" value="normal_search">
                    </div>
                </form>
            </div>
            <div class="page-list-mainer art-list">
                <table class="table table-hover officialdoc-table" id="officialdoc_table">
                    <thead>
                    <tr>
                        <th width="20">
                            <label class="checkbox">
                                <input type="checkbox" data-name="officialdoc[]">
                            </label>
                        </th>
                        <th width="32">
                            <i class="i-lt o-art-list"></i>
                        </th>
                        <th><?php echo Ibos::lang('Title'); ?></th>
                        <th width="120">发布者</th>
                        <th width="70">已查看</th>
                        <th width="70">已签收</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="page-list" id="doc_approval" style="display:none;">
            <div class="page-list-header">
                <div class="btn-toolbar pull-left">
                    <button class="btn btn-primary pull-left" id="approval_btn" data-action="verifyDoc">审核通过</button>
                    <button class="btn pull-left" id="doc_rollback" data-action="backDocs">退回</button>
                </div>
                <form action="javascript:;" method="post">
                    <div class="search search-config pull-right span3">
                        <input type="text" placeholder="输入标题查询" name="keyword" id="dn_search" nofocus
                               <?php if (Env::getRequest('param')): ?>value="<?php echo MainUtil::getCookie('keyword'); ?>"<?php endif; ?>>
                        <a href="javascript:;">search</a>
                        <input type="hidden" name="type" value="normal_search">
                    </div>
                </form>
            </div>
            <div class="page-list-mainer art-list">
                <table class="table table-hover officialdoc-table" id="approval_table">
                    <thead>
                    <tr>
                        <th width="20">
                            <label class="checkbox">
                                <input type="checkbox" data-name="approval[]">
                            </label>
                        </th>
                        <th><?php echo Ibos::lang('Title'); ?></th>
                        <th width="110">审核流程</th>
                        <th width="110">发布者</th>
                        <th width="80"></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <!-- Mainer content -->
    </div>
</div>

<!-- 高级搜索 -->
<div id="mn_search_advance" style="width: 400px; display:none;">
    <form id="mn_search_advance_form"
          action="<?php echo $this->createUrl('officialdoc/index', array('param' => 'search')); ?>" method="post">
        <div class="form-horizontal form-compact">
            <div class="control-group">
                <label for="" class="control-label"><?php echo Ibos::lang('Keyword'); ?></label>
                <div class="controls">
                    <input type="text" id="keyword" name="search[keyword]">
                </div>
            </div>
            <div class="control-group">
                <label for="" class="control-label"><?php echo Ibos::lang('Start time'); ?></label>
                <div class="controls">
                    <div class="datepicker" id="date_start">
                        <a href="javascript:;" class="datepicker-btn"></a>
                        <input type="text" class="datepicker-input" name="search[starttime]">
                    </div>
                </div>
            </div>
            <div class="control-group">
                <label for="" class="control-label"><?php echo Ibos::lang('End time'); ?></label>
                <div class="controls">
                    <div class="datepicker" id="date_end">
                        <a href="javascript:;" class="datepicker-btn"></a>
                        <input type="text" class="datepicker-input" name="search[endtime]">
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="type" value="advanced_search">
    </form>
</div>

<!-- 设置置顶 -->
<div id="dialog_art_top" class="form-horizontal form-compact" style="width: 400px; display:none;">
    <form action="javascript:;">
        <div class="control-group">
            <label class="control-label" id="test"><?php echo Ibos::lang('Expired time'); ?></label>
            <div class="controls">
                <div class="datepicker" id="date_time_totop">
                    <a href="javascript:;" class="datepicker-btn"></a>
                    <input type="text" class="datepicker-input" name="topEndTime" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
        </div>
    </form>
</div>

<!-- 高亮对话框  -->
<div id="dialog_art_highlight" class="form-horizontal form-compact" style="width: 400px; display:none;">
    <form action="javascript:;">
        <div class="control-group">
            <label class="control-label" id="test"><?php echo Ibos::lang('Expired time'); ?></label>
            <div class="controls">
                <div class="datepicker" id="date_time_highlight">
                    <a href="javascript:;" class="datepicker-btn"></a>
                    <input type="text" class="datepicker-input" name="highlightEndTime"
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
        </div>
        <div class="control-group">
            <div class="controls" id="simple_editor"></div>
            <input type="hidden" id="highlight_color" name="color">
            <input type="hidden" id="highlight_bold" name="bold" value="0">
            <input type="hidden" id="highlight_italic" name="italic" value="0">
            <input type="hidden" id="highlight_underline" name="underline" value="0">
        </div>
    </form>
</div>

<!--退回-->
<div id="rollback_reason" style="display:none;">
    <form action="javascript:;" method="post" id="rollback_form">
        <textarea rows="8" cols="60" name="reason" id="rollback_textarea" placeholder="退回理由...."></textarea>
    </form>
</div>

<script src="<?php echo STATICURL; ?>/js/lib/dataTable/js/jquery.dataTables.js?<?php echo VERHASH; ?>"></script><script src="<?php echo STATICURL; ?>/js/lib/dataTable/plugins/input.js?<?php echo VERHASH; ?>"></script>
<script src='<?php echo STATICURL; ?>/js/app/ibos.treeCategory.js?<?php echo VERHASH; ?>'></script>
<script src="<?php echo $assetUrl; ?>/js/lang/zh-cn.js?<?php echo VERHASH; ?>"></script>
<script src="<?php echo $assetUrl; ?>/js/officialdoc.js?<?php echo VERHASH; ?>"></script>
<script src="<?php echo $assetUrl; ?>/js/doc_officialdoc_index.js?<?php echo VERHASH; ?>"></script>

<!-- load script end -->
