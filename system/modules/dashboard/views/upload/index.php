<?php

use application\core\utils\Convert;
use application\core\utils\File;

?>
<style>
    .open-watermark-module {
        width: 500px;
    }

    .open-watermark-module label {
        width: 120px;
        display: inline-block;
    }
</style>
<link rel="stylesheet" href="<?php echo STATICURL; ?>/css/common.css?<?php echo VERHASH; ?>">
<div class="ct">
    <div class="clearfix">
        <h1 class="mt"><?php echo $lang['Common Setting'];?> > <?php echo $lang['Upload file limit']; ?></h1>
    </div>
    <div>
        <form action="" method="post" class="form-horizontal enterprise-info-form" id="upload_settings_form">
            <!-- 上传设置 start -->
            <div class="ctb">
                <div class="ctbw">
                    <?php 
                    if(ENGINE != 'SAAS' ):
                    ?>
                    <div class="control-group">
                        <label for="" class="control-label"><?php echo $lang['Local attachment dir']; ?></label>
                        <div class="controls">
                            <input type="text" name="attachdir" value="<?php echo $upload['attachdir']; ?>" readonly/>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"><?php echo $lang['Local attachment url']; ?></label>
                        <div class="controls">
                            <input type="text" name="attachurl" value="<?php echo $upload['attachurl']; ?>" readonly>
                        </div>
                    </div>
                    <?php 
                    endif;
                    ?>
                    <!-- @Todo: php -->
                    <div class="control-group">
                        <label class="control-label"><?php echo $lang['File type']; ?></label>
                        <div class="controls">
                            <textarea type="text" name="filetype" ><?php echo $upload['filetype']; ?></textarea>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"><?php echo $lang['File size limit']; ?></label>
                        <div class="controls">
                            <div class="row">
                                <div class="span6" style="margin-left: -10px;">
                                    <div class="input-group">
                                        <input type="text" name="attachsize" id="file_size" value="<?php echo $upload['attachsize']; ?>"/>
                                        <span class="input-group-addon">MB</span>

                                    </div>
                                </div>
                            </div>
                            最大限制：
                            <?php echo $size; ?>
                            <?php if(ENGINE != 'SAAS' ): ?>
                                上传最大限制修改方法：<a href='http://doc.ibos.com.cn/article/detail/id/270' target="_blank"/>点我</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="control-group <?php if (ENGINE == 'SAAS'):?> hidden <?php endif;?>">
                        <label class="control-label"><?php echo $lang['Thumb quality']; ?></label>
                        <div class="controls" data-toggle="tooltip">
                            <div id="thumbnail" data-value="<?php echo $upload['thumbquality']; ?>"></div>
                            <input type="hidden" id="thumbquality" name="thumbquality"
                                   value="<?php echo $upload['thumbquality']; ?>">
                        </div>
                    </div>
                </div>
            </div>
            <!-- 水印设置 start -->
            <?php 
            if(ENGINE != 'SAAS' ):
            ?>
            <div class="ctb">
                <h2 class="st"><?php echo $lang['Watermark setup']; ?></h2>
                <div class="ctbw">
                    <div class="control-group">
                        <label class="control-label"><?php echo $lang['Image watermark']; ?></label>
                        <div class="controls">
                            <input type="checkbox" name="watermarkstatus" value='0' id="watermark_enable"
                                   data-toggle="switch" class="visi-hidden"
                                   <?php if ($waterStatus == '1'): ?>checked<?php endif; ?>>
                        </div>
                    </div>
                    <div id="watermark_setup" <?php if ($waterStatus == '0'): ?>style="display: none;"<?php endif; ?>>

                        <div class="control-group">
                            <label class="control-label"><?php echo $lang['Watermark module']; ?></label>
                            <div class="controls open-watermark-module">
                                <?php foreach ($modules as $row): ?>
                                    <?php if ($row['module'] != 'baidu' && $row['iscore'] == '0'): ?>
                                        <label class="checkbox">
                                            <?php echo $row['name']; ?>
                                            <input type="checkbox" name="module[]"
                                                   <?php if (in_array($row['module'], $waterModule)) : ?>checked<?php endif; ?>
                                                   value="<?php echo $row['module']; ?>"/>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label"><?php echo $lang['Watermark type']; ?></label>
                            <div class="controls" id="watermark_type">
                                <label class="radio" data-target="#watermark_type_photo">
                                    <input type="radio" value='image' name="watermarktype"
                                           <?php if ($waterConfig['watermarktype'] == 'image'): ?>checked<?php endif; ?>/>
                                           <?php echo $lang['Watermark to image']; ?>
                                </label>
                                <label class="radio" data-target="#watermark_type_text">
                                    <input type="radio" value='text' name="watermarktype"
                                           <?php if ($waterConfig['watermarktype'] == 'text'): ?>checked<?php endif; ?>>
                                           <?php echo $lang['Watermark to text']; ?>
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"><?php echo $lang['Watermark position']; ?></label>
                            <div class="controls">
                                <div class="watermark" id="watermark_position">
                                    <label class="radius-tl"><input type="radio"
                                                                    <?php if ($waterConfig['watermarkposition'] == '1'): ?>checked<?php endif; ?>
                                                                    value='1' name="watermarkposition"/></label>
                                    <label><input type="radio" value='2'
                                                  <?php if ($waterConfig['watermarkposition'] == '2'): ?>checked<?php endif; ?>
                                                  name="watermarkposition"/></label>
                                    <label class="radius-tr"><input type="radio" value='3'
                                                                    <?php if ($waterConfig['watermarkposition'] == '3'): ?>checked<?php endif; ?>
                                                                    name="watermarkposition"/></label>
                                    <label><input type="radio" value='4'
                                                  <?php if ($waterConfig['watermarkposition'] == '4'): ?>checked<?php endif; ?>
                                                  name="watermarkposition"/></label>
                                    <label><input type="radio" value='5'
                                                  <?php if ($waterConfig['watermarkposition'] == '5'): ?>checked<?php endif; ?>
                                                  name="watermarkposition"/></label>
                                    <label><input type="radio" value='6'
                                                  <?php if ($waterConfig['watermarkposition'] == '6'): ?>checked<?php endif; ?>
                                                  name="watermarkposition"/></label>
                                    <label class="radius-bl"><input type="radio" value='7'
                                                                    <?php if ($waterConfig['watermarkposition'] == '7'): ?>checked<?php endif; ?>
                                                                    name="watermarkposition"/></label>
                                    <label><input type="radio" value='8' name="watermarkposition"
                                                  <?php if ($waterConfig['watermarkposition'] == '8'): ?>checked<?php endif; ?> /></label>
                                    <label class="radius-br"><input type="radio" value='9'
                                                                    <?php if ($waterConfig['watermarkposition'] == '9'): ?>checked<?php endif; ?>
                                                                    name="watermarkposition"/></label>
                                </div>
                            </div>
                        </div>
                        <div id="watermark_type_content">
                            <!-- 图片水印 start -->
                            <div id="watermark_type_photo"
                                 <?php if ($waterConfig['watermarktype'] !== 'image'): ?>style="display: none;"<?php endif; ?>>
                                <div class="control-group">
                                    <label class="control-label"><?php echo $lang['Watermark limit']; ?></label>
                                    <div class="controls">
                                        <div class="row">
                                            <div class="span6">
                                                <div class="input-group">
                                                    <span
                                                        class="input-group-addon"><?php echo $lang['Height']; ?></span>
                                                    <input type="text" name="watermarkminheight"
                                                           value='<?php echo $waterConfig['watermarkminheight']; ?>'/>
                                                    <span class="input-group-addon">px</span>
                                                </div>
                                            </div>
                                            <div class="span6">
                                                <div class="input-group">
                                                    <span class="input-group-addon"><?php echo $lang['Width']; ?></span>
                                                    <input type="text" name="watermarkminwidth"
                                                           value='<?php echo $waterConfig['watermarkminwidth']; ?>'/>
                                                    <span class="input-group-addon">px</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label"><?php echo $lang['Watermark image']; ?></label>
                                    <div class="controls">
                                        <div class="pull-left posr" id="upload_img_wrap">
                                            <img src="<?php echo File::imageName($waterConfig['watermarkimg']); ?>" class="custom-logo"
                                                 id="upload_img">
                                        </div>
                                        <div class="pull-right">
                                            <div id="upload_btn"></div>
                                        </div>
                                        <div id="file_target"></div>
                                        <input type="hidden" name="watermarkimg" id="watermark_img"
                                               value="<?php echo $waterConfig['watermarkimg']; ?>">
                                    </div>
                                </div>
                            </div>
                            <!-- 图片水印 end -->
                            <!-- 文字水印 start -->
                            <div id="watermark_type_text"
                                 <?php if ($waterConfig['watermarktype'] !== 'text'): ?>style="display: none;"<?php endif; ?>>
                                <div class="control-group">
                                    <label class="control-label"><?php echo $lang['Watermark fontpath']; ?></label>
                                    <div class="controls">
                                        <select id='watermark_fontpath' name='watermarktext[fontpath]'>
                                            <?php foreach ($fontPath as $font): ?>
                                                <option
                                                    <?php if ($waterConfig['watermarktext']['fontpath'] === $font): ?>selected<?php endif; ?>
                                                    value='<?php echo $font; ?>'><?php echo $font; ?></option>
                                                <?php endforeach; ?>
                                        </select>
                                        <p class='help-block'>
                                            <?php echo $lang['Watermark font tip']; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label"><?php echo $lang['Watermark text']; ?></label>
                                    <div class="controls">
                                        <input type="text" id="watermark_text" name="watermarktext[text]"
                                               value="<?php echo $waterConfig['watermarktext']['text']; ?>">
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label"><?php echo $lang['Watermark text size']; ?></label>
                                    <div class="controls">
                                        <div class="input-group">
                                            <input type="text" id="watermark_text_size" name="watermarktext[size]"
                                                   value="<?php echo $waterConfig['watermarktext']['size']; ?>">
                                            <span class="input-group-addon">px</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label"><?php echo $lang['Watermark text color']; ?></label>
                                    <div class="controls">
                                        <input type="hidden" name='watermarktext[color]' id="watermark_color_value"
                                               value="<?php echo $waterConfig['watermarktext']['color']; ?>">
                                        <div class="input-group">
                                            <button type="button" href="javascript:;" id="watermark_color_ctrl"
                                                    class="btn btn-fix"
                                                    style="background-color: rgb(<?php echo implode(',', Convert::hexColorToRGB($waterConfig['watermarktext']['color'])); ?>)"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- 文字水印 end -->
                        </div>
                        <div class="control-group">
                            <label class="control-label"><?php echo $lang['Watermark transparency']; ?></label>
                            <div class="controls">
                                <div id="watermark_opacity"
                                     data-value="<?php echo $waterConfig['watermarktrans']; ?>"></div>
                                <input type="hidden" id="watermarktrans" name="watermarktrans"
                                       value="<?php echo $waterConfig['watermarktrans']; ?>">
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"><?php echo $lang['Image quality']; ?></label>
                            <div class="controls">
                                <div id="photo_quality"
                                     data-value="<?php echo $waterConfig['watermarkquality']; ?>"></div>
                                <input type="hidden" id="watermarkquality" name="watermarkquality"
                                       value="<?php echo $waterConfig['watermarkquality']; ?>">
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label"><?php echo $lang['Watermark review']; ?></label>
                            <div class="controls">
                                <button class="btn" data-type="watermark-review"
                                        type="button"><?php echo $lang['Click to review']; ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
            endif;
            ?>
                <!-- office 文件预览-->
                <div class="control-group">
                    <label class="control-label"><?php echo $lang['Office Preview']; ?></label>
                    <div class="controls">
                        <label class="radio">
                            <input type="radio" value='0' name="officepreview"
                                   <?php if ($officePreview == 0): ?>checked<?php endif; ?> />
                            <?php echo $lang['Office Preview IE']; ?>
                        </label>
                        <label class="radio">
                            <input type="radio" value='1' name="officepreview"
                                   <?php if ($officePreview == 1): ?>checked<?php endif; ?> />
                            <?php echo $lang['Office Preview Yozosoft']; ?>
                            (该预览由会将文件上传到永中office服务器, 并且不能在线编辑)
                        </label>
                    </div>
                </div>
                <!-- office 文件预览-->
            </div>
            <div class="control-group">
                <label class="control-label"></label>
                <div class="controls">
                    <button type="submit" name="uploadSubmit"
                            class="btn btn-primary btn-large btn-submit"><?php echo $lang['Submit']; ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    Ibos.app.s({
        'siezLimit': '<?php echo $size; ?>'
    });
</script>
<script src='<?php echo STATICURL; ?>/js/lib/formValidator/formValidator.packaged.js?<?php echo VERHASH; ?>'></script>
<script src="<?php echo STATICURL; ?>/js/lib/webuploader/webuploader.js?<?php echo VERHASH; ?>"></script>
<script src="<?php echo STATICURL; ?>/js/lib/webuploader/handlers.js?<?php echo VERHASH; ?>"></script>
<script src="<?php echo $assetUrl; ?>/js/db_upload.js"></script>
