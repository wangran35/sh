<?php

use application\core\utils\Env;
use application\core\utils\Ibos;
use application\core\utils\Org;

?>
<!-- Sidebar -->
<div class="aside" id="aside">
    <div class="sbb sbbl sbbf">
        <ul class="nav nav-strip nav-stacked">
            <li>
                <a href="<?php echo $this->createUrl('schedule/index'); ?>">
                    <i class="o-cal-personal"></i>
                    <?php echo Ibos::lang('Personal'); ?>
                </a>
            </li>
            <?php if ($hasSubUid): ?>
                <li>
                    <a href="<?php echo $this->createUrl('schedule/subschedule'); ?>">
                        <i class="o-cal-underling"></i>
                        <?php echo Ibos::lang('Subordinate'); ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="active">
                <a href="<?php echo $this->createUrl('schedule/shareschedule'); ?>">
                    <i class="o-cal-shareme"></i>
                    <?php echo Ibos::lang('Share'); ?>
                </a>
                <div>
                    <ul class="mng-list" id="mng_list">
                        <?php if (!empty($deptArr)): ?>
                            <?php foreach ($deptArr as $dept): ?>
                                <li>
                                    <div class="mng-item mng-department active" data-action="toggleUnderlingsList">
                                        <span class="o-caret dept"><i class="caret"></i></span>
                                        <a href="javascript:;">
                                            <i class="o-org"></i>
                                            <?php echo $dept['deptname']; ?>
                                        </a>
                                    </div>
                                    <ul class="mng-scd-list cal-underling-list">
                                        <?php foreach ($dept['users'] as $user): ?>
                                            <li>
                                                <div class="mng-item sub">
                                                    <a href="<?php echo $this->createUrl('schedule/shareschedule', array('uid' => $user['uid'])); ?>"
                                                       <?php if (Env::getRequest('uid') == $user['uid']): ?>style="color:#3497DB;"<?php endif; ?>>
                                                        <img
                                                            src="<?php echo Org::getDataStatic($user['uid'], 'avatar', 'middle') ?>"
                                                            alt="">
                                                        <?php echo $user['realname']; ?>
                                                    </a>
                                                </div>
                                                <!--下属资料,ajax调用生成-->
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>

<script>
    $(function () {
        // 侧栏伸缩
        var $mngList = $("#mng_list");
        $mngList.on("click", ".view-all", function () {
            var $el = $(this);
            $.get(Ibos.app.url("calendar/schedule/shareSchedule", {op: "getshareordinates", item: "99999"}), {
                uid: $el.attr('data-uid')
            }, function (res) {
                $el.parent().replaceWith(res);
            });
        });

        Ibos.evt.add({
            // 展开/收起下属列表
            "toggleUnderlingsList": function (param, elem) {
                $(elem).toggleClass("active").next("ul").toggle();
            }
        })
    });
</script>