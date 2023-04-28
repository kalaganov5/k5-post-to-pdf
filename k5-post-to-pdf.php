<?php
/**
 * Plugin Name: WP Post to PDF
 * Plugin URI: https://kalaganov5.com
 * Description: Use shortcode `[pdf_download]`. A simple plugin to download WordPress articles as PDF files with support for the Russian language, Polylang, and shortcode.
 * Version: 1.0
 * Author: Vladimir Kalaganov
 * Author URI: https://kalaganov5.com
 * License: GPL2
 */

// Подключение DOMPDF
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Функция для генерации PDF
function k5_generate_pdf($html, $filename, $language, $title)
{
    // Настройка опций DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    // Установка пути к папке со шрифтами
    $options->set('fontDir', plugin_dir_path(__FILE__) . 'fonts/');

    // Выбор шрифта в зависимости от текущего языка
    switch ($language) {
        case 'ru':
            $options->set('defaultFont', 'DejaVu Sans');
            break;
        default:
            $options->set('defaultFont', 'Arial');
            break;
    }

    // Добавление заголовка к содержимому
    $header = '<h1 style="text-align:center;">' . $title . '</h1>';

    // Добавление стилей для легкого жирного стиля заголовков
    $styles = '
        <style>
            h2, h3, h4, h5, h6 {
                font-weight: 500;
            }
        </style>
    ';

    $html = $styles . $header . $html;

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');

    // Добавление шапки с названием сайта и ссылкой на текущую страницу
    $site_title  = get_bloginfo('name');
    $current_url = get_permalink();
    $header_html = "
        <div style='height: 20px; font-size: 10px;'>
            <table width='100%'>
                <tr>
                    <td><b style='font-size: 18px;'>$site_title</b></td>
                    <td style='text-align: right;'><a href='$current_url'>$current_url</a></td>
                </tr>
            </table>
        </div>
    ";

    // Добавление копирайта в подвал
    $copyright   = "© " . date('Y') . " " . $site_title . ". All rights reserved.";
    $footer_html = "
        <div style='height: 20px; font-size: 10px;'>
            $copyright
        </div>
    ";

    // Оборачивание основного контента
    $content_wrapper = "
        <div style='margin-top: 50px; margin-bottom: 30px;'>
            $html
        </div>
    ";

    $html = $header_html . $content_wrapper . $footer_html;

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => true));
}


// Обработчик запроса PDF
function k5_article_to_pdf()
{
    if (isset($_GET['pdf']) && is_singular()) {
        $post     = get_post();
        $html     = apply_filters('the_content', $post->post_content);
        $filename = sanitize_title($post->post_title) . '.pdf';

        // Получение текущего языка с помощью Polylang, если он активирован
        if (function_exists('pll_current_language')) {
            $current_language = pll_current_language('slug');
        } else {
            // Значение по умолчанию, если Polylang отключен или отсутствует
            $current_language = 'ru';
        }

        $title = $post->post_title;
        k5_generate_pdf($html, $filename, $current_language, $title);
    }
}

add_action('template_redirect', 'k5_article_to_pdf');

// Регистрация шорткода для добавления ссылки на PDF
function k5_article_to_pdf_shortcode()
{
    $pdf_link = add_query_arg('pdf', '1', get_permalink());

    return '<a href="' . esc_url($pdf_link) . '"class="button button--arrow">' . __(
            'Download the article',
            'k5-post-to-pdf'
        ) . '</a>';
}

add_shortcode('pdf_download', 'k5_article_to_pdf_shortcode');

function k5_post_to_pdf_plugin_load_textdomain()
{
    load_plugin_textdomain('k5-post-to-pdf', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('plugins_loaded', 'k5_post_to_pdf_plugin_load_textdomain');
