# szkub

Краткая инструкция по работе с вёрсткой.

## Структура
- `dist/` — рабочая папка (то, что потом пойдёт в Bitrix)
- `dist/css/` — стили (`main.css`, `accordion.css`, `page-*.css`)
- `dist/js/` — скрипты (`index.js`, `filters.js`, `accordion.js`)
- `dist/js/vendor/` — библиотеки (`nouislider.min.js` и т.д.)
- `dist/img/` — изображения
- `src/` — исходники для удобства вёрстки (SCSS/HTML/JS), можно держать как архив

## Установка
```bash
npm install
```

## Сборка (опционально)
```bash
npm run build
```

Результат:
- обновляется `dist/index.html`
- компилируется `dist/css/main.css` и `dist/css/accordion.css`
- копируются `dist/js/index.js`, `dist/js/filters.js`, `dist/js/accordion.js`
- копируется `dist/js/vendor/nouislider.min.js`
- копируются картинки в `dist/img`

## Примечания
- Бандла нет, все скрипты — отдельные файлы.
- Минификация и объединение можно добавить позже.
