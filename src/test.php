<?php

namespace thiagoalessio\TesseractOCR;

(new \thiagoalessio\TesseractOCR\TesseractOCR("ocr.jpg"))
                ->userWords($_ENV['APP_DIR'] . '/config/tesseract.txt')
                ->lang('deu')
                ->withoutTempFiles()
                ->psm(6)
                ->dpi(400)
                ->run();
