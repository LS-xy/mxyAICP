<?php
require_once __DIR__ . '/helpers.php';

function footer_clean($html)
{
    $html = (string)$html;
    $html = str_replace(["\r\n", "\r"], "\n", $html);

    $html = preg_replace('#<\s*(script|iframe|object|embed|form|input|button|link|meta|base)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
    $html = preg_replace('#<\s*(script|iframe|object|embed|form|input|button|link|meta|base)[^>]*/?>#is', '', $html);

    $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
    $html = preg_replace('#(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')#i', '$1="#"', $html);

    $html = preg_replace('#(href|src)\s*=\s*("|\')\s*(https?:)?//[^"\']*("|\')#i', '$1="#"', $html);

    return trim($html);
}

function footer_style_only($html)
{
    return strip_tags(footer_clean($html));
}

function footer_rules()
{
    return [
        '只收 HTML + 内联 CSS 片段，不要 <script>、<iframe>、<form>',
        '不要外链图片、字体、脚本，图片请转成 data URI',
        '不要 on* 事件属性（onclick、onload 等）',
        '单个片段不超过 20KB，不超过 60 行',
        '页面里用占位符 __CODE__ 和 __LINK__，生成代码时会自动替换',
        '内容需与「备案标识 / 站点徽标」相关，上传后由管理员审核',
    ];
}

function footer_templates()
{
    return [
        [
            'name' => '简洁胶囊（默认）',
            'html' => '<a href="__LINK__" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:8px;padding:7px 13px;border:1px solid #e6e6e6;border-radius:999px;background:#fff;color:#141414;text-decoration:none;font:500 12px/1 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif;box-shadow:0 1px 2px rgba(16,24,40,.04)"><span style="width:6px;height:6px;border-radius:50%;background:#16a34a;flex:0 0 auto"></span><span>mxyA</span><span style="color:#c4c4c4">·</span><span style="font-family:ui-monospace,Menlo,Consolas,monospace;color:#6b7280">__CODE__</span></a>',
        ],
        [
            'name' => '深色小票',
            'html' => '<a href="__LINK__" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:9px;padding:8px 14px;border-radius:10px;background:#141414;color:#f5f5f5;text-decoration:none;font:500 12px/1 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif"><span style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;letter-spacing:.14em;opacity:.65">ICP</span><span style="width:1px;height:12px;background:rgba(255,255,255,.25)"></span><span>__CODE__</span></a>',
        ],
        [
            'name' => '细线文字',
            'html' => '<a href="__LINK__" target="_blank" rel="noopener" style="display:inline-block;padding:2px 0;border-bottom:1px solid currentColor;color:#4b5563;text-decoration:none;font:400 12px/1.6 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Microsoft YaHei,sans-serif">备案标识 __CODE__</a>',
        ],
    ];
}

function footer_render($html, $code, $link, $site)
{
    $html = str_replace(['__CODE__', '__LINK__'], [htmlspecialchars($code, ENT_QUOTES), htmlspecialchars($link, ENT_QUOTES)], $html);

    return str_replace('mxyA', htmlspecialchars($site, ENT_QUOTES), $html);
}
