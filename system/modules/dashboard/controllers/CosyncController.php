<?php

/**
 * 酷办公同步用户以及组织架构控制器
 * CosyncController.class.file
 * @link http://www.ibos.com.cn/
 * @copyright Copyright &copy; 2015 IBOS Inc
 * @package application.modules.dashboard.controllers
 * @author Sam <gzxgs@ibos.com.cn>
 */

namespace application\modules\dashboard\controllers;

use application\core\components\AliyunLog;
use application\core\components\AliyunLogFilter;
use application\core\model\Log;
use application\core\utils\Api;
use application\core\utils\Env;
use application\core\utils\Ibos;
use application\core\utils\Org;
use application\core\utils\StringUtil;
use application\modules\dashboard\model\Cache;
use application\modules\department\model\Department as DepartmentModel;
use application\modules\department\model\Department;
use application\modules\department\model\DepartmentBinding;
use application\modules\main\model\Setting;
use application\modules\message\core\co\CoApi;
use application\modules\message\core\co\CodeApi;
use application\modules\position\model\Position;
use application\modules\user\model\User;
use application\modules\user\model\UserBinding;
use CJSON;
use Exception;

class CosyncController extends CoController
{

    /**
     * 酷办公官网地址
     * @var string
     */
    protected $courl = 'https://www.ibos.cn/api/sync/';

    protected $aeskey;

    /**
     * 酷办公绑定需要提供的绑定类型
     * 在 IBOS 默认为 ibos
     * @var string
     */
    protected $coBindType = 'ibos';

    /**
     * 酷办公类型标识
     * 在 IBOS 默认为 co
     * @var string
     */
    protected $coType = 'co';

    /**
     * 需要绑定的酷办公企业 ID
     * @var integer
     */
    protected $corpid;

    public function init()
    {
        parent::init();
        if (isset($this->coinfo['corpid'])):
            $this->corpid = $this->coinfo['corpid'];
        else:
            //这个判断是为了保证发布的6427版本之前的版本已经绑定酷办公的不会出现错误
            //如果是旧的绑定流程，this->coinfo里面就没有corpid，这里强制把cobinding标识改成未绑定状态
            Setting::model()->updateSettingValueByKey('cobinding', 0);
            return $this->redirect('cobinding/index');
        endif;
    }

    /**
     * 同步首页视图
     */
    public function actionIndex()
    {
        // 判断是后台访问还是安装访问
        // 后台访问比前台需要的数据多一点
        $isInstall = $this->verifyIsInstall();
        if (!empty($isInstall)) {
            $data = array(
                'co' => $isInstall['co'],
                'ibos' => $isInstall['ibos'],
            );
        }
        $data['isInstall'] = Env::getRequest('isInstall') != 1 ? 0 : 1;
        $data['pageInit'] = 'index';
        // 是否开启了自动同步
        $autoSync = StringUtil::utf8Unserialize(Setting::model()->fetchSettingValueByKey('autosync'));
        $data['autoSync'] = isset($autoSync['status']) ? $autoSync['status'] : '0';
        $this->render('index', $data);
    }

    /**
     * 同步操作 ajax 调用
     */
    public function actionSync()
    {
        $op = Env::getRequest('op');
        $isInstall = Env::getRequest('isInstall');
        $opList = array('init', 'deptinit', 'dept', 'syncIbosUser', 'syncCoUser', 'buildRelation', 'updateCache');
        $msgPlatform = 'co';
        if (!in_array($op, $opList)) {
            // 先是不带 op 参数访问 sync 视图，将同步进行中的页面内容显示出来
            // 再根据 op 参数去进行对应的同步步骤 && 修改相应的显示内容
            if ($op === null) {
                // 判断是后台访问还是安装访问
                // 后台访问比前台需要的数据多一点
                $data = $this->verifyIsInstall();
                $data['isInstall'] = $isInstall;

                $this->ajaxReturn(array(
                    'status' => 1,
                    'data' => $data,
                    'op' => 'init',
                    'progress' => '0%',
                ));
            } else {
                $this->ajaxReturn(array(
                    'status' => 2,
                    'message' => '请求的参数有误',
                ));
            }
        }
        set_time_limit(120);
        switch ($op) {
            case 'init':
                // 准备同步结果数据记录字段
                if (Cache::model()->fetchArrayByPk('successinfo') === false) {
                    Cache::model()->add(array('cachekey' => 'successinfo', 'cachevalue' => serialize(array())));
                } else {
                    Cache::model()->updateByPk('successinfo', array('cachevalue' => serialize(array())));
                }
                // 先把酷办公那边的部门拿下来
                $this->syncCoDept();
                $this->ajaxReturn(array(
                    'status' => 1,
                    'message' => '开始同步酷办公部门，请稍后...',
                    'op' => 'deptinit',
                    'progress' => '10%',
                ));
                break;
            case 'deptinit':
                $type = 'co';
                $deptCount = DepartmentModel::model()->CountUnbind($type);
                $codept = array(
                    'deptlevel' => 0,
                    'deptcount' => $deptCount,
                );
                Ibos::app()->user->setState('codept', $codept);
                $this->ajaxReturn(array(
                    'status' => 1,
                    'message' => '酷办公部门已同步过来，接着同步Ibos部门..',
                    'op' => 'dept',
                    'progress' => '20%',
                ));
                break;
            case 'dept':  // 参考企业号部门同步 WxsyncController
                $codept = Ibos::app()->user->codept;
                $level = $codept['deptlevel'];
                $i = 10;
                $type = 'co';
                while ($i--) {
                    //这个使用子查询的方式去遍历一棵树
                    $deptPer = DepartmentModel::model()->getPerDept($level, $type);
                    if (!empty($deptPer)) {
                        $codept['deptlevel'] = $level;
                        break;
                    } else {
                        //当前层级找不到数据，则尝试着下一层级找部门
                        $level++;
                    }
                }
                Ibos::app()->user->setState('codept', $codept);
                if (empty($deptPer)) {
                    return $this->ajaxReturn(array(
                        'status' => 1,
                        'message' => '同步部门完成,开始处理用户,请稍后..',
                        'op' => 'syncIbosUser',
                        'progress' => '35%',
                    ));
                } else {
                    $res = $this->createCoDeptByList($deptPer);
                    $this->ajaxReturn(array(
                        'res' => $res,
                        'status' => 1,
                        'message' => '正在同步部门，请耐心等候...',
                        'op' => 'dept',
                        'progress' => '30%',
                    ));
                }
                break;
            case 'syncIbosUser': // 同步酷办公用户下来
                $this->coSendUser();
                $this->ajaxReturn(array(
                    'status' => 1,
                    'message' => '开始同步酷办公用户，接着同步IBOS用户...',
                    'op' => 'syncCoUser',
                    'progress' => '45%',
                ));
                break;
            case 'syncCoUser': // IBOS传用户数据给酷办公同步
                $limit = 1000;
                $offset = 0;
                $userArray = $this->sendSyncUser($msgPlatform, $limit, $offset);
                if (empty($userArray['bind']) && empty($userArray['delete'])) {
                    $this->ajaxReturn(array(
                        'status' => 1,
                        'message' => '同步成功！更新缓存..',
                        'op' => 'updateCache',
                        'progress' => '90%',
                    ));
                } else {
                    $this->ajaxReturn(array(
                        'status' => 1,
                        'message' => '同步中...',
                        'op' => 'syncCoUser',
                        'progress' => '60%',
                    ));
                }
                break;
            case 'updateCache': // IBOS更新缓存
                $successInfo = Cache::model()->fetchArrayByPk('successinfo');
                $relationCount = UserBinding::model()->count("`app` = 'co'");
                $successInfo['syncCountNum'] = $relationCount;
                Org::update();
                $this->ajaxReturn(array(
                    'status' => 0,
                    'message' => '更新完成！',
                    'data' => $successInfo,
                    'progress' => '100%',
                ));
                break;
            default :
                break;
        }
    }

    /**
     * 获取同步差异数据
     * @return ajax
     */
    public function actionGetSyncList()
    {
        // 准备 Cache 表的相关 key 值记录
        $this->readySync();
        // 获取用户同步数据列表
        $syncList = $this->getSyncList();
        $syncList['third']['delete'] = $this->removeAdminUidFromIbosRemoveList($syncList['third']['delete']);
        Cache::model()->updateByPk('iboscreatelist', array('cachevalue' => serialize($syncList['third']['add'])));
        Cache::model()->updateByPk('ibosremovelist', array('cachevalue' => serialize($syncList['third']['delete'])));
        $data = array(
            'co' => array(
                'coAddNum' => count($syncList['third']['add']),
                'coAddList' => $syncList['third']['add'],
                'coDelNum' => count($syncList['third']['delete']),
                'coDelList' => $syncList['third']['delete'],
                'count' => $syncList['third']['count'],
            ),
            'ibos' => array(
                'ibosAddNum' => $syncList['co']['add'],
//                'ibosAddList' => $syncList['co']['add'],
                'ibosDelNum' => $syncList['co']['delete'],
//                'ibosDelList' => $syncList['co']['delete'],
                'count' => $syncList['co']['count'],
            ),
        );
        $this->ajaxReturn(array(
            'status' => true,
            'data' => $data,
        ));
    }

    /**
     * 根据参数获取对应准备同步的用户分类信息列表
     * ibosAddList    IBOS 新增/启用 用户信息列表
     * ibosDelList    IBOS 禁用 用户信息列表
     * coAddList    酷办公 新增/加入 用户信息列表
     * coDelList    酷办公 移除 用户信息列表
     * @return ajax
     */
    public function actionGetUserListInfo()
    {
        $dataList = array();
        $op = Env::getRequest('op');
        $opList = array('ibosAddList', 'ibosDelList', 'coAddList', 'coDelList');
        if (!in_array($op, $opList)) {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'message' => '请求的参数不正确',
            ));
        }
        switch ($op) {
            case 'ibosAddList':
                $start = Env::getRequest('start');
                $length = Env::getRequest('length');
                if (isset($start) && isset($length)) {
                    $coids = User::model()->fetchPartUnbind($start, $length);
                    $coCreateList = User::model()->findThreeByUid($coids);
                } else {
                    $coids = User::model()->fetchUnbind();
                    $coCreateList = User::model()->findThreeByUid($coids);
                }
                foreach ($coCreateList as $key => $user) {
                    $dataList[] = array(
                        'realname' => $user['realname'],
                        'deptname' => DepartmentModel::model()->fetchDeptNameByUid($user['uid']),
                        'posname' => Position::model()->fetchPosNameByUid($user['uid']),
                    );
                }
                break;
            case 'ibosDelList':
                $removeids = User::model()->fetchDeletebind();
                $coRemoveList = User::model()->findThreeByUid($removeids);
                foreach ($coRemoveList as $key => $user) {
                    $dataList[] = array(
                        'realname' => $user['realname'],
                        'deptname' => DepartmentModel::model()->fetchDeptNameByUid($user['uid']),
                        'posname' => Position::model()->fetchPosNameByUid($user['uid']),
                    );
                }
                break;
            case 'coAddList':
                $dataList = Cache::model()->fetchArrayByPk('iboscreatelist');
                break;
            case 'coDelList':
                $dataList = Cache::model()->fetchArrayByPk('ibosremovelist');
                break;
        }
        $this->ajaxReturn(array(
            'isSuccess' => true,
            'data' => $dataList,
        ));
    }

    /**
     * 检查是否需要自动同步
     * @return boolen
     */
    public function isAutoSync()
    {
        if (Setting::model()->fetchSettingValueByKey('autosync') === false) {
            Setting::model()->add(array('skey' => 'autosync', 'svalue' => serialize(array('status' => 1, 'lastsynctime' => time()))));
        }
        $autosync = StringUtil::utf8Unserialize(Setting::model()->fetchSettingValueByKey('autosync'));
        // 开启了自动同步 && 上一次同步时间小于今天 0 点
        if ($autosync['status'] == 1 && $autosync['lastsynctime'] < strtotime(date('Y-m-d', time()))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 开启关闭自动同步
     */
    public function actionAutoSync()
    {
        $status = Env::getRequest('autoSync');
        $autosync = StringUtil::utf8Unserialize(Setting::model()->fetchSettingValueByKey('autosync'));
        $autosync['status'] = intval($status);
        if (Setting::model()->updateSettingValueByKey('autosync', serialize($autosync))) {
            $this->ajaxReturn(array(
                'status' => true,
                'message' => ($status === '1' ? '开启' : '关闭') . '自动同步成功',
            ));
        } else {
            $this->ajaxReturn(array(
                'status' => false,
                'message' => ($status === '1' ? '开启' : '关闭') . '自动同步失败',
            ));
        }
    }


    /**
     * 按照酷办公需要的数据格式获取 IBOS 用户信息列表
     * @return array
     */
    protected function getUserList()
    {
        $userList = User::model()->fetchAll('status = 0');
        foreach ($userList as $user) {
            $result[] = array(
                'uid' => $user['uid'],
                'realname' => $user['realname'],
                'mobile' => $user['mobile'],
            );
        }
        return isset($result) ? $result : array();
    }

    /**
     * 返回同步用户列表
     * @return array 同步用户列表
     * @internal param array $userList IBOS 用户列表
     */
    protected function getSyncList()
    {
        // 查出Ibos新增和禁用的用户
        $add = $delete = '';
        $result = array();
        $count = User::model()->count('status != :status', array('status' => User::USER_STATUS_ABANDONED));
        $delete = User::model()->CountDelete();
        $add = User::model()->CountUnbind();
        $getSync = $this->getUsersFromCo();
        if ($getSync['errorcode'] == CodeApi::SUCCESS) {
            $result['data'] = array(
                'co' => array(
                    'add' => $add['0'],
                    'delete' => $delete['0'],
                    'count' => $count
                ),
                'third' => $getSync['data']['third']
            );
            return $result['data'];
        } else {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'msg' => $getSync,
            ));
        }
    }

    /**
     * 检查 Cache 表中是否有对应 key 记录
     * 没有就添加
     * iboscreatelist 需要 IBOS 新增的用户
     * ibosremovelist 需要 IBOS 移除的用户
     * cocreatelist 需要酷办公新增的用户
     * coremovelist 需要酷办公移除的用户
     */
    protected function readySync()
    {
        // 准备同步结果数据记录字段
        if (Cache::model()->fetchArrayByPk('successinfo') === false) {
            Cache::model()->add(array('cachekey' => 'successinfo', 'cachevalue' => serialize(array())));
        }
        // 需要 IBOS 新增的用户
        if (Cache::model()->fetchArrayByPk('iboscreatelist') === false) {
            Cache::model()->add(array('cachekey' => 'iboscreatelist', 'cachevalue' => serialize(array())));
        }
        // 需要 IBOS 移除的用户
        if (Cache::model()->fetchArrayByPk('ibosremovelist') === false) {
            Cache::model()->add(array('cachekey' => 'ibosremovelist', 'cachevalue' => serialize(array())));
        }
    }

    /**
     * 比较两个用户数组中的手机号，相同的话从数组中去除，将手机号相同的两个数组中用户 uid 组成第三个数组
     * 第三个数组直接表示的是需要绑定的 uid 关系 ['uid_1' => $user_1['uid'], 'uid_2' => $user_2['uid']]
     * 返回去重后的两个用户数组 & uid 组成的第三个数组
     * @param  array $userList_1 包含 mobile 的用户数组
     * @param  array $userList_2 包含 mobile 的用户数组
     * @return array
     */
    protected function removeIdenticalByMobile($userList_1, $userList_2)
    {
        $identical = array();
        if (!is_array($userList_1) || !is_array($userList_2)) {
            $this->ajaxReturn(array(
                'status' => 5,
                'message' => '请尝试手动点击开始同步',
            ));
        }
        foreach ($userList_1 as $key_1 => $user_1) {
            foreach ($userList_2 as $key_2 => $user_2) {
                if ($user_1['mobile'] === $user_2['mobile']) {
                    $identical[] = array('uid_1' => array('uid' => $user_1['uid'], 'guid' => $user_1['guid']), 'uid_2' => $user_2['uid']);
                    unset($userList_1[$key_1]);
                    unset($userList_2[$key_2]);
                    break;
                }
            }
        }
        return array(
            'userList_1' => $userList_1,
            'userList_2' => $userList_2,
            'identicalList' => $identical,
        );
    }

    /**
     * 调用酷办公接口
     * 根据 IBOS 提供的绑定关系列表，创建酷办公的绑定关系
     * array(
     *     array( 'uid' => [酷办公用户 uid], 'bindvalue' => [IBOS 用户 uid] ),
     *     array( 'uid' => [酷办公用户 uid], 'bindvalue' => [IBOS 用户 uid] ),
     *     array( 'uid' => [酷办公用户 uid], 'bindvalue' => [IBOS 用户 uid] ),
     *     ...
     * )
     * @param  array $relationCreateList IBOS 的绑定关系列表
     */
    protected function coCreateRelation($relationCreateList)
    {
        $post = array(
            'type' => $this->coBindType,
            'corpid' => $this->corpid,
            'data' => $relationCreateList,
        );
        $createCoRelationRes = CoApi::getInstance()->createRelationByList($post);
        if ($createCoRelationRes['errorcode'] != CodeApi::SUCCESS) {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'messag' => $createCoRelationRes['message'],
            ));
        }
    }

    /**
     * 调用酷办公接口
     * 根据 IBOS 提供的绑定关系列表，删除酷办公的绑定关系
     * array(
     *     array( 'uid' => [酷办公用户 uid], 'bindvalue' => [IBOS 用户 uid] ),
     *     array( 'uid' => [酷办公用户 uid], 'bindvalue' => [IBOS 用户 uid] ),
     *     array( 'uid' => [酷办公用户 uid], 'bindvalue' => [IBOS 用户 uid] ),
     *     ...
     * )
     * @param  array $relationRemoveList 需要酷办公删除的绑定关系列表
     */
    protected function coRemoveRelation($relationRemoveList)
    {
        $post = array(
            'type' => $this->coBindType,
            'corpid' => $this->corpid,
            'data' => $relationRemoveList,
        );
        $removeCoRelationRes = CoApi::getInstance()->removeRelationByList($post);
        if ($removeCoRelationRes['errorcode'] != CodeApi::SUCCESS) {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'messag' => $removeCoRelationRes['message'],
            ));
        }
    }

    /**
     * 调用酷办公创建用户接口，将 IBOS 新增的用户同步到酷办公
     * array(
     *     array(
     *         'uid' => [IBOS 用户 uid],
     *         'realname' => [IBOS 用户真实姓名],
     *         'mobile' => [IBOS 用户手机号]
     *     ),
     *     array(
     *         'uid' => [IBOS 用户 uid],
     *         'realname' => [IBOS 用户真实姓名],
     *         'mobile' => [IBOS 用户手机号]
     *     ),
     *     array(
     *         'uid' => [IBOS 用户 uid],
     *         'realname' => [IBOS 用户真实姓名],
     *         'mobile' => [IBOS 用户手机号]
     *     ),
     *     ...
     * )
     * @param $userCreateList
     * @return array
     * @internal param array $coreadysynclist IBOS 新增的用户列表
     */
    protected function createCoUser($userCreateList)
    {
        $post = array(
            'type' => $this->coBindType,
            'corpid' => $this->corpid,
            'data' => $userCreateList,
        );
        $createCoUserRes = CoApi::getInstance()->createCoUserByList($post);
        // 调用接口成功，根据返回数据添加相应的绑定记录
        if ($createCoUserRes['errorcode'] == CodeApi::SUCCESS) {
            foreach ($createCoUserRes['data'] as $relation) {
                $this->addBindRelation($relation['uid'], $relation['bindvalue']);
            }
            return $createCoUserRes['data'];
        } else {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'message' => $createCoUserRes['message'],
            ));
        }
    }

    /**
     * 调用酷办公移除用户接口，将 IBOS 移除的用户同步到酷办公
     * array(
     *     array(
     *         'uid' => [IBOS 用户 uid],
     *         'realname' => [IBOS 用户真实姓名],
     *         'mobile' => [IBOS 用户手机号]
     *     ),
     *     array(
     *         'uid' => [IBOS 用户 uid],
     *         'realname' => [IBOS 用户真实姓名],
     *         'mobile' => [IBOS 用户手机号]
     *     ),
     *     array(
     *         'uid' => [IBOS 用户 uid],
     *         'realname' => [IBOS 用户真实姓名],
     *         'mobile' => [IBOS 用户手机号]
     *     ),
     *     ...
     * )
     * @param $userRemoveList
     * @return array
     * @internal param array $ibosRemoveList IBOS 移除的用户列表
     */
    protected function removeCoUser($userRemoveList)
    {
        $post = array(
            'type' => $this->coBindType,
            'corpid' => $this->corpid,
            'data' => $userRemoveList,
        );
        $removeCoUserRes = CoApi::getInstance()->removeCoUserByList($post);
        // 调用接口成功，根据返回数据删除对应的绑定记录
        if ($removeCoUserRes['errorcode'] == CodeApi::SUCCESS) {
            foreach ($removeCoUserRes['data'] as $relation) {
                UserBinding::model()->deleteAll(sprintf("`uid` = %d AND `app` = 'co'", $relation['uid']));
            }
            return $removeCoUserRes['data'];
        } else {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'message' => 14 . $removeCoUserRes['message'],
            ));
        }
    }

    /**
     * 添加绑定关系
     * @param integer $uid IBOS uid
     * @param integer $bindvalue 酷办公 uid
     * @return integer 成功数
     */
    protected function addBindRelation($uid, $bindvalue)
    {
        static $successNum = 0;
        $condition = "`uid` = :uid AND `app` = 'co'";
        $params = array(':uid' => $uid);
        $userBind = UserBinding::model()->fetch($condition, $params);
        if (!empty($userBind)) {
            UserBinding::model()->deleteAll(sprintf("`uid` = %d AND `app` = 'co'", $uid));
        }
        $addRes = UserBinding::model()->add(array('uid' => $uid, 'bindvalue' => $bindvalue, 'app' => 'co'));
        if ($addRes) {
            $successNum++;
        }
        return $successNum;
    }

    /**
     * 判断是安装时进行的同步操作还是后台绑定
     * 后台绑定时的同步，需要加上绑定企业双方的一些基本数据，用于资料展示
     * @return array 后台时会返回企业的 logo 等资料数组。安装时不需要，返回空数组
     */
    protected function verifyIsInstall()
    {
        $isInstance = Env::getRequest('isInstance');
        if ($isInstance === null) {
//			$this->coinfo = StringUtil::utf8Unserialize( Setting::model()->fetchSettingValueByKey( 'coinfo' ) );
            $unit = StringUtil::utf8Unserialize(Setting::model()->fetchSettingValueByKey('unit'));
            $result['ibos'] = array(
                'corplogo' => $unit['logourl'],
                'corpshortname' => $unit['shortname'],
                'systemurl' => $unit['systemurl'],
            );
            $result['co'] = array(
                'corplogo' => $this->coinfo['corplogo'],
                'corpshortname' => $this->coinfo['corpshortname'],
                'corpid' => $this->coinfo['corpid'],
            );
        } else {
            $result = array();
        }
        return $result;
    }

    /**
     * 过滤酷办公移除用户列表中的 IBOS 超级管理员用户
     * @param  array $ibosRemoveList 从差异化分析接口返回的酷办公移除用户列表
     * @return array                 过滤后的用户列表
     */
    protected function removeAdminUidFromIbosRemoveList($ibosRemoveList)
    {
        $bindvalue = UserBinding::model()->fetchBindValue(1, 'co');
        if (!empty($bindvalue)) {
            foreach ($ibosRemoveList as $key => $user) {
                if ($bindvalue == $user['uid']) {
                    unset($ibosRemoveList[$key]);
                }
            }
        }
        return $ibosRemoveList;
    }

    /**
     * 调用酷办公创建部门接口，将 IBOS 新增的部门同步到酷办公
     * array(
     *   array(
     *       'deptname' => [IBOS 部门名字],
     *       'deptid' => [IBOS 部门id],
     *       'pid' => [IBOS 部门父id],
     *       'sort' => [IBOS 部门顺序],
     *       ),
     *   array
     *   ...
     * )
     * @param  array $departments IBOS 新增的部门
     * @param  boolean $ifBind 是否需要将拿下来的部门添加到绑定表
     * @return boolean
     */
    protected function createCoDeptByList($departments, $ifBind = true)
    {
        $post = array(
            'type' => $this->coBindType,
            'corpid' => $this->corpid,
            'data' => $departments,
        );
        $res = CoApi::getInstance()->createCoDept($post);
        // 调用接口成功，根据返回数据添加相应的绑定记录
        if ($ifBind && $res['errorcode'] == CodeApi::SUCCESS) {
            $codept = Ibos::app()->user->codept;
            foreach ($res['data'] as $relation) {
                $bindNum = $this->addDeptBind($relation['deptid'], $relation['bindvalue'], true);
                $codept['deptcount'] -= $bindNum;
            }
            Ibos::app()->user->setState('codept', $codept);
            return true;
        } else if (!$ifBind && $res['errorcode'] == CodeApi::SUCCESS) {
            return true;
        } else {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'message' => $res['message'],
            ));
        }
    }

    /**
     * 添加部门绑定关系
     * @param $deptid
     * @param integer $bindvalue 酷办公 uid
     * @param boolean $flag 返回不返回成功数
     * @return int $bindNum 成功数
     * @internal param int $uid IBOS uid
     */
    protected function addDeptBind($deptid, $bindvalue, $flag = false)
    {
        static $bindNum = 0;
        $condition = "`deptid` = :deptid AND `app` = 'co'";
        $params = array(':deptid' => $deptid);
        $deptBind = DepartmentBinding::model()->fetch($condition, $params);
        if (!empty($deptBind)) {
            DepartmentBinding::model()->deleteAll(sprintf("`deptid` = %d AND `app` = 'co'", $deptid));
        }
        $addRes = DepartmentBinding::model()->add(array('deptid' => $deptid, 'bindvalue' => $bindvalue, 'app' => 'co'));
        if ($addRes) {
            $bindNum++;
        }
        if ($flag) {
            return $bindNum;
        } else {
            return true;
        }
    }

    /** 调用酷办公接口获取酷办公部门，拿下来同步并绑定关系
     */
    protected function syncCoDept()
    {
        $post = array(
            'type' => $this->coBindType,
            'corpid' => $this->corpid,
        );
        $res = CoApi::getInstance()->getCoDept($post);
        $bindDepts = $coDepts = array();
        $records = DepartmentBinding::model()->fetchAllBindvalue('co');
        foreach ($records as $record) {
            $bindDepts[$record['bindvalue']] = $record['deptid'];
        }
        // 调用接口成功，根据返回数据添加相应的绑定记录,将部门插入ibos
        if ($res['errorcode'] == CodeApi::SUCCESS) {
            $connection = Ibos::app()->db;
            $transaction = $connection->beginTransaction();
            try {
                foreach ($res['data'] as $relation) {
                    if (in_array($relation['deptid'], $bindDepts)) {
                        continue;
                    } else {
                        $pid = $relation['pid'];
                        if (array_key_exists($relation['pid'], $bindDepts)) {
                            $relation['pid'] = $bindDepts[$relation['pid']];
                        }
                        //创建部门->写进绑定表
                        $deptNames = Department::model()->getDeptNamesByPid($relation['pid']);
                        if (!in_array($relation['deptname'], $deptNames)) {
                            $newId = Department::model()->add(array('pid' => $relation['pid'], 'deptname' => $relation['deptname']), true);
                        } else {
                            $newId = Department::model()->getDeptIdByPidAndName($relation['pid'], $relation['deptname']);
                        }
                        $bindDepts[$relation['deptid']] = $newId;
                        DepartmentBinding::model()->add(array(
                            'deptid' => $newId,
                            'bindvalue' => $relation['deptid'],
                            'app' => 'co',
                        ));
                        // 拿出创建和绑定好的放到coDepts里面，上传给酷办公绑定
                        $coDepts[] = array(
                            'deptid' => $newId,
                            'pid' => $pid,
                            'deptname' => $relation['deptname'],
                            'sort' => $newId
                        );
                    }
                }
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
            }
            $this->createCoDeptByList($coDepts, false);
            return true;
        } else {
            $this->ajaxReturn(array(
                'isSuccess' => false,
                'message' => $res['message'],
                'cobinding' => false, // 这里只有企业被注销的情况下出现
            ));
        }
    }

    /**
     * 酷办公发送用户过来
     */
    protected function coSendUser()
    {
        $url = $this->getUrl('usersync');
        $res = Api::getInstance()->fetchResult($url, '', 'post');
        if (is_string($res)) {
            $array = CJSON::decode($res);
            // 更新缓存表
            $this->updateSuccessInfo($array, 'ibos');
            if (!empty($array['data']['offset'])) {
                $this->coSendUser();
            }else{
                return true;
            }
        }
    }

    /**
     * 传用户数据上去
     * @param $msgPlatform
     * @param $limit
     * @param $offset
     * @return array
     */
    protected function sendSyncUser($msgPlatform, $limit, $offset)
    {
        $bind = User::model()->fetchNotBind($msgPlatform, $limit, $offset);
        $delete = User::model()->fetchHasBindButDel($msgPlatform, $limit, $offset);
        $userArray = array('bind' => $bind, 'delete' => $delete);
        if (empty($userArray['bind']) && empty($userArray['delete'])) {
            // 更新缓存表
            $successInfo = Cache::model()->fetchArrayByPk('successinfo');
            if (!isset($successInfo['coCreateNum'])) {
                $successInfo['coCreateNum'] = '0';
            }
            if (!isset($successInfo['coRemoveNum'])) {
                $successInfo['coRemoveNum'] = '0';
            }
            Cache::model()->updateByPk('successinfo', array('cachevalue' => serialize($successInfo)));
            return array();
        }
        $url = $this->getUrl('syncuser');
        $res = Api::getInstance()->fetchResult($url, CJSON::encode($userArray), 'post');
        if (is_string($res)) {
            $array = CJSON::decode($res);
            if(!is_null($array)){
                if (false === $array['isSuccess']) {
                    $this->ajaxReturn(array(
                        'isSuccess' => false,
                        'message' => $array['msg'],
                    ));
                }
                !empty($array['data']['bind']) && $this->userBind($msgPlatform, $array['data']['bind']);
                !empty($array['data']['delete']) && $this->userDelete($msgPlatform, $array['data']['delete']);
                // 更新缓存表
                $this->updateSuccessInfo($array, 'co');
            }else{ 
                //如果返回的数据出错，则跳过当前操作继续到下一步。
                $userArray = array('bind' =>array() ,'delete'=>array() );
            }
        }
        return $userArray;
    }

    /**
     * 绑定用户
     * @param $msgPlatform
     * @param $array
     */
    protected function userBind($msgPlatform, $array)
    {
        foreach ($array as $uid => $bindvalue) {
            Ibos::app()->db->createCommand()->delete(UserBinding::model()->tableName(), 'uid = :uid', array(
                ':uid' => $uid,
            ));
            Ibos::app()->db->createCommand()->insert(UserBinding::model()->tableName(), array(
                'uid' => $uid,
                'bindvalue' => $bindvalue,
                'app' => $msgPlatform,
            ));
        }
    }

    /**
     * 禁用用户
     * @param $msgPlatform
     * @param $array
     */
    protected function userDelete($msgPlatform, $array)
    {
        foreach ($array as $uid => $bindvalue) {
            Ibos::app()->db->createCommand()
                ->delete('{{user_binding}}'
                    , " `uid` = '{$uid}' AND"
                    . " `app` = '{$msgPlatform}' ");
            Ibos::app()->db->createCommand()
                ->update('{{user}}'
                    , array('status' => 2)
                    , " `uid` = '{$uid}' ");
        }
    }

    /**
     * 构造url
     * @param $type
     * @return
     */
    protected function getUrl($type)
    {
        $api = $this->courl . $type;
        $time = time();
        return Api::getInstance()->buildUrl($api, array(
            'signature' => sha1($this->aeskey . $time),
            'timestamp' => $time,
            'corpid' => $this->corpid,
            'type' => 'ibos',
        ));
    }

    /**
     * 绑定部门
     * @param $msgPlatform
     * @param $array
     */
    protected function deptBind($msgPlatform, $array)
    {
        foreach ($array as $deptid => $bindvalue) {
            Ibos::app()->db->createCommand()
                ->insert('{{department_binding}}', array(
                    'deptid' => $deptid,
                    'bindvalue' => $bindvalue,
                    'app' => $msgPlatform
                ));
        }
    }

    /**
     * 更新指定successInfo
     * @param $array
     * @param $type
     * @return bool
     */
    protected function updateSuccessInfo($array, $type)
    {
        $successInfo = Cache::model()->fetchArrayByPk('successinfo');
        $create = $type . 'CreateNum';
        $remove = $type . 'RemoveNum';
        if (isset($array['data']['bind']) && !empty($array['data']['bind'])) {
            $bind = $array['data']['bind'];
            $bind = ($type == 'ibos') ? $bind : count($bind);
        }
        if (isset($array['data']['delete']) && !empty($array['data']['delete'])) {
            $delete = $array['data']['delete'];
            $delete = ($type == 'ibos') ? $delete : count($delete);
        }
        if (!isset($successInfo[$create])) {
            $successInfo[$create] = isset($bind) ? $bind : 0;
        } else {
            $successInfo[$create] += isset($bind) ? $bind : 0;
        }
        if (!isset($successInfo[$remove])) {
            $successInfo[$remove] = isset($delete) ? $delete : 0;
        } else {
            $successInfo[$remove] += isset($delete) ? $delete : 0;
        }
        Cache::model()->updateByPk('successinfo', array('cachevalue' => serialize($successInfo)));
        return true;
    }

    /**
     * 获取酷办公统计数据
     * @return mixed
     */
    protected function getUsersFromCo()
    {
        //请求酷办公的用户数据
        $post = array(
            'type' => $this->coBindType,
            'corpid' => $this->corpid,
        );
        $result = CoApi::getInstance()->getCoUsers($post);
        return $result;
    }
}
