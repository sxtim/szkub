<?php

if (!function_exists("szcubeGetProjectSelectorSceneConfigs")) {
    function szcubeGetProjectSelectorSceneConfigs()
    {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $templatePath = defined("SITE_TEMPLATE_PATH") ? SITE_TEMPLATE_PATH : "/local/templates/szcube";

        $config = array(
            "kollekciya" => array(
                "scene_mode" => "single_building",
                "data_project_code" => "vertical",
                "scene_image" => $templatePath . "/img/projects/image_15.jpg",
                "mobile_scene_image" => $templatePath . "/img/projects/kollecttsiya-mobile.jpg",
                "scene_svg_path" => $templatePath . "/img/projects/Group.svg",
                "map_url" => "",
                "map_label" => "На карте",
                "scene" => array(
                    "overlay" => array(
                        "left" => "19%",
                        "top" => "18%",
                        "width" => "42%",
                    ),
                    "mobile_overlay" => array(
                        "left" => "12.2%",
                        "top" => "23.4%",
                        "width" => "78.5%",
                    ),
                    "mobile_zoom" => "1.6",
                    "mobile_center_x" => "62%",
                ),
                "board" => array(
                    "rows_count" => 6,
                    "top_row_label" => "6-7",
                ),
                "pins" => array(
                    "1" => array(
                        "desktop" => array(
                            "left" => "55%",
                            "top" => "21%",
                        ),
                        "mobile" => array(
                            "left" => "58.5%",
                            "top" => "26%",
                        ),
                        "card" => array(
                            "side" => "right",
                            "offset_x" => "0px",
                            "offset_y" => "8px",
                        ),
                    ),
                    "2" => array(
                        "desktop" => array(
                            "left" => "23%",
                            "top" => "38%",
                        ),
                        "mobile" => array(
                            "left" => "18%",
                            "top" => "37%",
                        ),
                        "card" => array(
                            "side" => "left",
                            "offset_x" => "0px",
                            "offset_y" => "8px",
                        ),
                    ),
                ),
            ),
        );

        return $config;
    }
}

if (!function_exists("szcubeGetProjectSelectorSceneConfig")) {
    function szcubeGetProjectSelectorSceneConfig($projectCode)
    {
        $projectCode = trim((string)$projectCode);
        if ($projectCode === "") {
            return array();
        }

        $configs = szcubeGetProjectSelectorSceneConfigs();
        return isset($configs[$projectCode]) && is_array($configs[$projectCode]) ? $configs[$projectCode] : array();
    }
}
