<?php

namespace application\modules\email\controllers;

use application\core\utils\Cache;
use application\core\utils\Env;
use application\core\utils\Ibos;
use application\core\utils\StringUtil;
use application\modules\dashboard\controllers\BaseController;
use application\modules\main\model\Setting;

class DashboardController extends BaseController
{

    /**
     * setting配置项
     * @var array
     */
    private $_fields = array('emailexternalmail', 'emailrecall', 'emailsystemremind', 'emailroleallocation', 'emaildefsize');

    public function actionIndex()
    {
        $emailSetting = array();
        $setting = Ibos::app()->setting->get('setting');
        foreach ($this->_fields as $field) {
            $emailSetting[$field] = $setting[$field];
        }
        if (!empty($emailSetting['emailroleallocation'])) {
            foreach ($emailSetting['emailroleallocation'] as $key => $value) {
                if(strpos($key,',') !== false){
                    $newkey = str_replace(',','_', $key);
                    $emailSetting['emailroleallocation'][$newkey] = $emailSetting['emailroleallocation'][$key];
                    unset($emailSetting['emailroleallocation'][$key]);
                }
            }
        }
        $data['setting'] = $emailSetting;
        $this->render('index', $data);
    }

    /**
     * 修改数据
     */
    public function actionEdit()
    {
        if (Env::submitCheck('emailSubmit')) {
            $setting = array();
            foreach ($this->_fields as $field) {
                if (array_key_exists($field, $_POST)) {
                    $setting[$field] = intval($_POST[$field]);
                } else {
                    $setting[$field] = 0;
                }
            }
            $roles = array();
            if (isset($_POST['role'])) {
                foreach ($_POST['role'] as $role) {
                    if (!empty($role['positionid']) && !empty($role['size'])) {
                        $positionId = StringUtil::getId($role['positionid']);
                        $roles[implode(',', $positionId)] = intval($role['size']);
                    }
                }
            }
            $setting['emailroleallocation'] = serialize($roles);
            foreach ($setting as $key => $value) {
                Setting::model()->updateSettingValueByKey($key, $value);
            }
            Cache::update('setting');
            $this->success(Ibos::lang('Update succeed', 'message'), $this->createUrl('dashboard/index'));
        }
    }

}
