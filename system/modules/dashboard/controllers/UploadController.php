<?php

namespace application\modules\dashboard\controllers;

use application\core\utils\Cache;
use application\core\utils\Convert;
use application\core\utils\Env;
use application\core\utils\File;
use application\core\utils\Ibos;
use application\core\utils\Image;
use application\core\utils\StringUtil;
use application\extensions\ThinkImage\ThinkImage;
use application\modules\dashboard\utils\Dashboard;
use application\modules\main\model\Setting;
use CHtml;
use CJSON;

class UploadController extends BaseController
{

    const TTF_FONT_PATH = 'data/font/'; // 默认字体存放文件夹

    /**
     * 上传与水印设置
     * @return void
     */

    public function actionIndex()
    {
        $operation = Env::getRequest('op');
        switch ($operation) {
            case 'thumbpreview':// 缩略图预览
            case 'waterpreview':// 水印预览
                if(!File::fileExists(File::getTempPath())){
                    File::makeDir(File::getTempPath());
                }
                $temp = File::getTempPath() . '/watermark_temp.jpg';
                $quality = Env::getRequest('quality');
                // 原图
                $source = 'static/image/watermark_preview.jpg';
                if ($operation == 'waterpreview') {
                    $trans = Env::getRequest('trans', 'GP', 80);
                    $type = Env::getRequest('type');
                    $val = Env::getRequest('val'); //要是这个数据有误，会出错
                    $pos = Env::getRequest('pos', 'GP', 0);
                    // 图片水印
                    if ($type == 'image') {
                        $imgHeight = Env::getRequest('watermarkminheight', 'GP', '40');
                        $imgWidth = Env::getRequest('watermarkminwidth', 'GP', '120');
                        File::waterPic($source, $val, $temp, $pos, $trans, $quality, $imgHeight, $imgWidth);
                    } else {
                        // 文字水印
                        $hexColor = Env::getRequest('textcolor', '#ffffff'); //默认是#ffffff(白色)，格式不做判断
                        $size = intval(Env::getRequest('size', 16)); //过滤无效输入
                        $size = ($size > 0 && $size <= 48) ? $size : 16; //文字水印大小限制在1-48
                        $fontPath = Env::getRequest('fontpath', 'msyh.ttf'); //字体默认是微软雅黑
                        //文字水印需要判断文字内容是否为空
                        $val = (!empty($val)) ? $val : 'IBOS'; //空时默认是IBOS
                        File::waterString($val, $size, $source, $temp, $pos, $trans, $quality, $hexColor, self::TTF_FONT_PATH . $fontPath);
                    }
                    $image = $temp;
                }
                $data = array(
                    'image' => File::imageName($image),
                    'sourceSize' => Convert::sizeCount(File::fileSize($source)),
                    'thumbSize' => Convert::sizeCount(File::fileSize($image)),
                    'ratio' => (sprintf("%2.1f", File::fileSize($image) / File::fileSize($source) * 100)) . '%',
                    'time' => time()
                );
                $this->render('imagePreview', $data);
                exit();
                break;
            case 'upload': // 水印图片上传
                return $this->imgUpload('watermark', true);
                break;
        }
        $formSubmit = Env::submitCheck('uploadSubmit');
        //获取服务器上传最大限制
        $sizeArr = array(
            ini_get('upload_max_filesize'), ini_get('post_max_size'), ini_get('memory_limit'));
        $sizeArray = StringUtil::ConvertBytes($sizeArr);
        $minKey = 0;
        foreach ($sizeArray as $key => $row) {
            if ($row == min($sizeArray)) {
                $minKey = $key;
            }
        }
        $size = $sizeArr[$minKey];
        if ($formSubmit) {
            //如果设置的大小超过了上传限制，取上传限制为最终值
            $attachSize = CHtml::encode($_POST['attachsize']);
            if ($attachSize * 1024 * 1024 >= min($sizeArray)) {
                $attachSize = $size;
            }
            $uploadArray = array(
                'attachdir' => isset($_POST['attachdir']) ? CHtml::encode($_POST['attachdir']) : 'data/attachment',
                'attachurl' => isset($_POST['attachurl']) ? CHtml::encode($_POST['attachurl']) : 'data/attachment',
                'thumbquality' => CHtml::encode($_POST['thumbquality']),
                'attachsize' => $attachSize,
                'filetype' => CHtml::encode($_POST['filetype']),
            );
            $status = !empty($_POST['watermarkstatus']) ? 1 : 0;
            $waterArray = array(
                'watermarkminwidth' => isset($_POST['watermarkminwidth']) ? CHtml::encode($_POST['watermarkminwidth']) : '40',
                'watermarkminheight' => isset($_POST['watermarkminheight']) ? CHtml::encode($_POST['watermarkminheight']) : '120',
                'watermarktype' => isset($_POST['watermarktype']) ? CHtml::encode($_POST['watermarktype']) : 'text',
                'watermarkposition' => isset($_POST['watermarkposition']) ? CHtml::encode($_POST['watermarkposition']) : '9',
                'watermarktrans' => isset($_POST['watermarktrans']) ? CHtml::encode($_POST['watermarktrans']) : '50',
                'watermarkquality' => isset($_POST['watermarkquality']) ? CHtml::encode($_POST['watermarkquality']) : '90',
                'watermarkimg' => isset($_POST['watermarkimg']) ? trim(CHtml::encode($_POST['watermarkimg'])) : 'static/image/watermark_preview.jpg',
                'watermarktext' => array(
                    'fontpath' => isset($_POST['watermarktext']) ? self::TTF_FONT_PATH . CHtml::encode($_POST['watermarktext']['fontpath']) : '',
                    'text' => isset($_POST['watermarktext']) ? CHtml::encode($_POST['watermarktext']['text']) : 'Welcome to use the IBOS!',
                    'size' => isset($_POST['watermarktext']) ? CHtml::encode($_POST['watermarktext']['size']) : '12',
                    'color' => isset($_POST['watermarktext']) ? CHtml::encode($_POST['watermarktext']['color']) : '#3497DB',
                ),
                //'watermarkfontpath' => CHtml::encode($_POST['watermarkfontpath']), //暂时没有地方食用，不知道干嘛的
            );
            $waterConfigArray = array(
                'waterconfig' => CJSON::encode($waterArray),
                'watermodule' => CJSON::encode(Env::getRequest('module', 'P', array())),
                'watermarkstatus' => $status,
            );
            $officePreview = !isset($_POST['officepreview']) ? 0 : $_POST['officepreview'];
            Setting::model()->updateAll(array('svalue' => $uploadArray['attachdir']), "skey = 'attachdir' ");
            Setting::model()->updateAll(array('svalue' => $uploadArray['attachurl']), "skey = 'attachurl' ");
            Setting::model()->updateAll(array('svalue' => $uploadArray['thumbquality']), "skey = 'thumbquality' ");
            Setting::model()->updateAll(array('svalue' => $uploadArray['attachsize']), "skey = 'attachsize' ");
            Setting::model()->updateAll(array('svalue' => $uploadArray['filetype']), "skey = 'filetype' ");
            Setting::model()->updateAll(array('svalue' => $waterConfigArray['waterconfig']), "skey = 'waterconfig' ");
            Setting::model()->updateAll(array('svalue' => $waterConfigArray['watermodule']), "skey = 'watermodule' ");
            Setting::model()->updateAll(array('svalue' => $waterConfigArray['watermarkstatus']), "skey = 'watermarkstatus' ");
            Setting::model()->addOrUpdateSetting('officepreview', $officePreview);
            Cache::update(array('setting'));
            $this->success(Ibos::lang('Save succeed', 'message'));
        } else {
            $upload = Setting::model()->fetchSettingValueByKeys('attachdir,attachurl,thumbquality,attachsize,filetype');
            $upload['attachsize'] = str_replace('M', '', strtoupper($upload['attachsize'])); // 去除输入框中的M标识,该M标识来自配置
            $waterStatus = Setting::model()->fetchSettingValueByKey('watermarkstatus');
            $waterConfig = Setting::model()->fetchSettingValueByKey('waterconfig');
            $waterModule = Setting::model()->fetchSettingValueByKey('watermodule');
            $officePreview = Setting::model()->fetchSettingValueByKey('officepreview'); // office 预览方式
            $officePreview = empty($officePreview) ? 0 : $officePreview; // 默认为普通的ie插件模式
            $fontPath = Dashboard::getFontPathlist(self::TTF_FONT_PATH);
            $modules = Ibos::app()->getEnabledModule();
            $moduleArray = array_merge($modules, array('baidu' => array('name' => '百度编辑器', 'module' => 'baidu')));
            $data = array(
                'size' => $size,
                'upload' => $upload,
                'waterStatus' => $waterStatus,
                'waterConfig' => CJSON::decode($waterConfig),
                'waterModule' => CJSON::decode($waterModule),
                'fontPath' => $fontPath,
                'modules' => $moduleArray,
                'officePreview' => $officePreview,
            );
            $this->render('index', $data);
        }
    }

}
