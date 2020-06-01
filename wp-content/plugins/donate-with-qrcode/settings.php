<?php
/**
 * This was contained in an addon until version 1.0.0 when it was rolled into
 * core.
 *
 * @package    WBOLT
 * @author     WBOLT
 * @since      1.4.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019, WBOLT
 */

$pd_title = '博客社交分享组件';
$pd_version = DWQR_VERSION;
$pd_code = 'dwq-setting';
$pd_index_url = 'https://www.wbolt.com/plugins/dwq';
$pd_doc_url = 'https://www.wbolt.com/dwq-plugin-documentation.html';

wp_enqueue_media();
?>

<div style=" display:none;">
    <svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <defs>
            <symbol id="sico-upload" viewBox="0 0 16 13">
                <path d="M9 8v3H7V8H4l4-4 4 4H9zm4-2.9V5a5 5 0 0 0-5-5 4.9 4.9 0 0 0-4.9 4.3A4.4 4.4 0 0 0 0 8.5C0 11 2 13 4.5 13H12a4 4 0 0 0 1-7.9z" fill="#666" fill-rule="evenodd"/>
            </symbol>
            <symbol id="sico-download" viewBox="0 0 16 16">
                <path d="M9 9V0H7v9H4l4 4 4-4z"/><path d="M15 16H1a1 1 0 0 1-1-1.1l1-8c0-.5.5-.9 1-.9h3v2H2.9L2 14H14L13 8H11V6h3c.5 0 1 .4 1 .9l1 8a1 1 0 0 1-1 1.1"/>
            </symbol>
            <symbol id="sico-wb-logo" viewBox="0 0 18 18">
                <title>sico-wb-logo</title>
                <path d="M7.264 10.8l-2.764-0.964c-0.101-0.036-0.172-0.131-0.172-0.243 0-0.053 0.016-0.103 0.044-0.144l-0.001 0.001 6.686-8.55c0.129-0.129 0-0.321-0.129-0.386-0.631-0.163-1.355-0.256-2.102-0.256-2.451 0-4.666 1.009-6.254 2.633l-0.002 0.002c-0.791 0.774-1.439 1.691-1.905 2.708l-0.023 0.057c-0.407 0.95-0.644 2.056-0.644 3.217 0 0.044 0 0.089 0.001 0.133l-0-0.007c0 1.221 0.257 2.314 0.643 3.407 0.872 1.906 2.324 3.42 4.128 4.348l0.051 0.024c0.129 0.064 0.257 0 0.321-0.129l2.25-5.593c0.064-0.129 0-0.257-0.129-0.321z"></path>
                <path d="M16.714 5.914c-0.841-1.851-2.249-3.322-4.001-4.22l-0.049-0.023c-0.040-0.027-0.090-0.043-0.143-0.043-0.112 0-0.206 0.071-0.242 0.17l-0.001 0.002-2.507 5.914c0 0.129 0 0.257 0.129 0.321l2.571 1.286c0.129 0.064 0.129 0.257 0 0.386l-5.979 7.264c-0.129 0.129 0 0.321 0.129 0.386 0.618 0.15 1.327 0.236 2.056 0.236 2.418 0 4.615-0.947 6.24-2.49l-0.004 0.004c0.771-0.771 1.414-1.671 1.929-2.7 0.45-1.029 0.643-2.121 0.643-3.279s-0.193-2.314-0.643-3.279z"></path>
            </symbol>
            <symbol id="sico-more" viewBox="0 0 16 16">
                <path d="M6 0H1C.4 0 0 .4 0 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1M15 0h-5c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1M6 9H1c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1v-5c0-.6-.4-1-1-1M15 9h-5c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1v-5c0-.6-.4-1-1-1"/>
            </symbol>
            <symbol id="sico-plugins" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M16 3h-2V0h-2v3H8V0H6v3H4v2h1v2a5 5 0 0 0 4 4.9V14H2v-4H0v5c0 .6.4 1 1 1h9c.6 0 1-.4 1-1v-3.1A5 5 0 0 0 15 7V5h1V3z"/>
            </symbol>
            <symbol id="sico-doc" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 0H1C.4 0 0 .4 0 1v14c0 .6.4 1 1 1h14c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1zm-1 2v9h-3c-.6 0-1 .4-1 1v1H6v-1c0-.6-.4-1-1-1H2V2h12z"/><path d="M4 4h8v2H4zM4 7h8v2H4z"/>
            </symbol>
            
            <symbol id="wbsico-donate" viewBox="0 0 9 18">
                <path fill-rule="evenodd" d="M5.63 8.1V4.61c.67.23 1.12.9 1.12 1.58S7.2 7.3 7.88 7.3 9 6.86 9 6.2a3.8 3.8 0 0 0-3.38-3.83V1.12C5.63.45 5.17 0 4.5 0S3.37.45 3.37 1.12v1.24A3.8 3.8 0 0 0 0 6.2C0 8.55 1.8 9.45 3.38 9.9v3.49c-.68-.23-1.13-.9-1.13-1.58S1.8 10.7 1.12 10.7 0 11.14 0 11.8a3.8 3.8 0 0 0 3.38 3.83v1.24c0 .67.45 1.12 1.12 1.12s1.13-.45 1.13-1.12v-1.24A3.88 3.88 0 0 0 9 11.8c0-2.36-1.8-3.26-3.38-3.7zM2.25 6.19c0-.79.45-1.35 1.13-1.58v2.93c-.8-.34-1.13-.68-1.13-1.35zm3.38 7.2v-2.93c.78.34 1.12.68 1.12 1.35 0 .79-.45 1.35-1.13 1.58z"></path>
            </symbol>
            <symbol id="wbsico-like" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M13.3 6H9V2c0-1.5-.8-2-2-2-.3 0-.6.2-.6.5L4 8v8h8.6c1.3 0 2.4-1 2.6-2.3l.8-4.6c.1-.8-.1-1.6-.6-2.1-.5-.7-1.3-1-2.1-1M0 8h2v8H0z"/>
            </symbol>
            <symbol id="wbsico-share" viewBox="0 0 14 16">
                <path fill-rule="evenodd" d="M11 6a3 3 0 1 0-3-2.4L5 5.6A3 3 0 0 0 3 5a3 3 0 0 0 0 6 3 3 0 0 0 1.9-.7l3.2 2-.1.7a3 3 0 1 0 3-3 3 3 0 0 0-1.9.7L6 8.7a3 3 0 0 0 0-1.3l3.2-2A3 3 0 0 0 11 6"/>
            </symbol>
            <symbol id="wbsico-time" viewBox="0 0 18 18">
                <path d="M9 15.75c-3.71 0-6.75-3.04-6.75-6.75S5.29 2.25 9 2.25 15.75 5.29 15.75 9 12.71 15.75 9 15.75zM9 0C4.05 0 0 4.05 0 9s4.05 9 9 9 9-4.05 9-9-4.05-9-9-9z"/>
                <path d="M10.24 4.5h-1.8V9h4.5V7.2h-2.7z"/>
            </symbol>
            <symbol id="wbsico-views" viewBox="0 0 26 18">
                <path d="M13.1 0C7.15.02 2.08 3.7.02 8.9L0 9a14.1 14.1 0 0 0 13.09 9c5.93-.02 11-3.7 13.06-8.9l.03-.1A14.1 14.1 0 0 0 13.1 0zm0 15a6 6 0 0 1-5.97-6v-.03c0-3.3 2.67-5.97 5.96-5.98a6 6 0 0 1 5.96 6v.04c0 3.3-2.67 5.97-5.96 5.98zm0-9.6a3.6 3.6 0 1 0 0 7.2 3.6 3.6 0 0 0 0-7.2h-.01z"/>
            </symbol>
            <symbol id="wbsico-comment" viewBox="0 0 18 18">
                <path d="M9 0C4.05 0 0 3.49 0 7.88s4.05 7.87 9 7.87c.45 0 .9 0 1.24-.11L15.75 18v-4.95A7.32 7.32 0 0 0 18 7.88C18 3.48 13.95 0 9 0z"/>
            </symbol>
            <symbol id="wbsico-poster" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M14 0a2 2 0 012 2v12a2 2 0 01-2 2H2a2 2 0 01-2-2V2C0 .9.9 0 2 0h12zm0 2H2v12h12V2zm-6 9a1 1 0 110 2 1 1 0 010-2zm5-8v7H3V3h10z"/>
            </symbol>
        </defs>
    </svg>
</div>

<div id="optionsframework-wrap" class="wbs-wrap wbps-wrap v-wp" data-wba-source="<?php echo $pd_code; ?>" v-cloak>
    <div class="wbs-header">
        <svg class="wb-icon sico-wb-logo"><use xlink:href="#sico-wb-logo"></use></svg>
        <span>WBOLT</span>
        <strong><?php echo $pd_title; ?></strong>

        <div class="links">
            <a class="wb-btn" href="<?php echo $pd_index_url; ?>" data-wba-campaign="title-bar" target="_blank">
                <svg class="wb-icon sico-plugins"><use xlink:href="#sico-plugins"></use></svg>
                <span>插件主页</span>
            </a>
            <a class="wb-btn" href="<?php echo $pd_doc_url; ?>" data-wba-campaign="title-bar" target="_blank">
                <svg class="wb-icon sico-doc"><use xlink:href="#sico-doc"></use></svg>
                <span>说明文档</span>
            </a>
        </div>
    </div>

    <div class="wbs-content option-form" id="optionsframework">
        <div class="sc-body ">
            <h4 class="sc-title-sub">
                <span>基本设置</span>
            </h4>

            <table class="wbs-form-table">
                <tbody>
                <tr>
                    <th class="row w8em">
                        功能开关
                    </th>
                    <td>
                        <input class="wb-switch" type="checkbox" v-model="opt.dwqr_switch" true-value="1" false-value="0"> <span class="description">开启后，将在文章详情底部显示打赏按钮</span>
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-show="opt.dwqr_switch == '1'">
                <table class="wbs-form-table">
                    <tbody>
                    <tr>
                        <th class="row w8em">
                            选择组件
                        </th>
                        <td>
                            <div class="selector-bar" id="J_typeItems">
                                <label><input type="checkbox" v-model="opt.dwqr_module.donate" true-value="1" false-value="0"> 打赏</label>
                                <label><input type="checkbox" v-model="opt.dwqr_module.like" true-value="1" false-value="0"> 点赞</label>
                                <label><input type="checkbox" v-model="opt.dwqr_module.poster" true-value="1" false-value="0"> 微海报</label>
                                <label><input type="checkbox" v-model="opt.dwqr_module.share" true-value="1" false-value="0"> 分享</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th class="row">配色方案</th>
                        <td>
                            <div class="themes-color-items">
                                <label v-for="color in cnf.theme_color">
                                    <input type="radio" :value="color" :style="'background-color:'+ color + ';'" v-model="opt.theme_color" :checked="opt.theme_color == color">
                                </label>
                                <label>
                                    <input class="wbs-input w8em" id="J_posterThemeColorPiker" v-model="opt.theme_color" placeholder="">
                                    <span class="description">*可输入自定义色值</span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            样式预览
                        </th>
                        <td>
                            <div class="wbp-cbm preview-box info" id="J_typePreview" v-bind:style="'--dwqrColor:'+opt.theme_color">
                                <div class="ctrl-item">
                                    <a class="wb-btn-dwqr" v-if="opt.dwqr_module.donate == '1'"><svg class="wb-icon wbsico-donate"><use xlink:href="#wbsico-donate"></use></svg><span>打赏</span></a>
                                    <a class="wb-btn-dwqr" v-if="opt.dwqr_module.like == '1'"><svg class="wb-icon wbsico-like"><use xlink:href="#wbsico-like"></use></svg><span>赞(0)</span></a>
                                    <a class="wb-btn-dwqr" v-if="opt.dwqr_module.poster == '1'"><svg class="wb-icon wbsico-share"><use xlink:href="#wbsico-poster"></use></svg><span>微海报</span></a>
                                    <a class="wb-btn-dwqr" v-if="opt.dwqr_module.share == '1'"><svg class="wb-icon wbsico-share"><use xlink:href="#wbsico-share"></use></svg><span>分享</span></a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>


            <h4 class="sc-title-sub" v-if="opt.dwqr_module.donate == '1'">
                <span>打赏二维码设置</span>
            </h4>

            <table class="wbs-form-table" v-if="opt.dwqr_module.donate == '1'">
                <tr v-for="v in opt.items">
                    <th class="row w8em">{{v.name}}收款二维码</th>
                    <td>
                        <wbs-upload-box v-bind:url="v.img" v-on:get-file="v.img = $event"></wbs-upload-box>
                        <p class="description">* 请上传1:1尺寸规格的{{v.name}}收款二维码图片，<a href="https://www.wbolt.com/how-to-get-wechat-and-alipay-qr-code.html" data-wba-campaign="Setting-Des-txt" target="_blank">如何获取{{v.name}}收款二维码</a>？</p>
                    </td>
                </tr>
            </table>


            <h4 class="sc-title-sub" v-if="opt.dwqr_module.poster == '1'">
                <span>微海报设置</span>
            </h4>

            <table class="wbs-form-table" v-if="opt.dwqr_module.poster == '1'">
                <tr>
                    <th class="row w8em">站点logo图片</th>
                    <td>
                        <wbs-upload-box v-bind:url="opt.logo_url" v-on:get-file="opt.logo_url = $event"></wbs-upload-box>
                        <p class="description">* 请上传站点的logo图片，用于微海报生成。</p>
                    </td>
                </tr>
                <tr>
                    <th class="row">海报默认图</th>
                    <td>
                        <wbs-upload-box v-bind:url="opt.cover_url" v-on:get-file="opt.cover_url = $event"></wbs-upload-box>
                        <p class="description">* 当文章没有特色图及其他图片时，会使用默认图作为海报头图。建议选择与如下设定的海报比例一致的图片。</p>
                    </td>
                </tr>
                <tr v-if="0">
                    <th class="row">海报图比例</th>
                    <td>
                        <div class="selector-bar">
                            <label v-for="v in cnf.poster_cover_ratio">
                                <input type="radio" :value="v" v-model="opt.cover_ratio" :checked="opt.cover_ratio == v">
                                <span>{{v}}</span>
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th class="row">微海报样式</th>
                    <td>
                        <div class="poster-themes">
                            <label v-for="(theme, key) in cnf.poster_theme">
                                <div class="theme-name">
                                    <input type="radio" :value="key"  v-model="opt.poster_theme" :checked="opt.poster_theme == key">
                                    <span>{{theme.name}}</span>
                                </div>
                                <div class="theme-demo"><img :src="'https://static.wbolt.com/wp-content/uploads/2020/05/dwq_theme_' + (key+1) + '.png'"></div>
                            </label>
                        </div>
                    </td>
                </tr>
            </table>

        </div>

        <more-wb-info v-bind:utm-source="opt.pd_code"></more-wb-info>

    </div>

    <div class="wb-copyright-bar">
        <div class="wbcb-inner">
            <a class="wb-logo" href="https://www.wbolt.com" data-wba-campaign="footer" title="WBOLT" target="_blank"><svg class="wb-icon sico-wb-logo"><use xlink:href="#sico-wb-logo"></use></svg></a>
            <div class="wb-desc">
                Made By <a href="https://www.wbolt.com" data-wba-campaign="footer" target="_blank">闪电博</a>
                <span class="wb-version">版本：<?php echo $pd_version;?></span>
            </div>
            <div class="ft-links">
                <a href="https://www.wbolt.com/plugins" data-wba-campaign="footer" target="_blank">免费插件</a>
                <a href="https://www.wbolt.com/knowledgebase" data-wba-campaign="footer" target="_blank">插件支持</a>
                <a href="<?php echo $pd_doc_url; ?>" data-wba-campaign="footer" target="_blank">说明文档</a>
                <a href="https://www.wbolt.com/terms-conditions" data-wba-campaign="footer" target="_blank">服务协议</a>
                <a href="https://www.wbolt.com/privacy-policy" data-wba-campaign="footer" target="_blank">隐私条例</a>
            </div>
        </div>
    </div>

    <div class="wbs-footer" id="optionsframework-submit">
        <div class="wbsf-inner">
            <button class="wbs-btn-primary" type="button" name="update" @click="updateData">保存设置</button>
        </div>
    </div>
</div>




