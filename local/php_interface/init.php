<?php

if (PHP_SAPI !== "cli" && !headers_sent()) {
    $host = strtolower((string)($_SERVER["HTTP_HOST"] ?? ""));
    $host = preg_replace("/:\\d+$/", "", $host);

    if (in_array($host, ["szcube.ru", "www.szcube.ru"], true)) {
        $isHttps = false;

        if (!empty($_SERVER["HTTPS"]) && strtolower((string)$_SERVER["HTTPS"]) !== "off") {
            $isHttps = true;
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"])) {
            $isHttps = strtolower((string)$_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https";
        }

        if ($host !== "szcube.ru" || !$isHttps) {
            $requestUri = (string)($_SERVER["REQUEST_URI"] ?? "/");
            if ($requestUri === "") {
                $requestUri = "/";
            }

            $method = strtoupper((string)($_SERVER["REQUEST_METHOD"] ?? "GET"));
            $statusCode = in_array($method, ["GET", "HEAD"], true) ? 301 : 308;

            header("Location: https://szcube.ru" . $requestUri, true, $statusCode);
            exit;
        }
    }
}

