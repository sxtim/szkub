# szkub

Bitrix-проект: код в Git, локальная разработка в Docker (env-docker), прод обновляется из GitHub.

## Структура
- `docs/` — исходная верстка
- `local/templates/szcube/` — шаблон Bitrix (css/js/img/fonts + header/footer)
- `index.php` — главная
- `consulting/` — страница консалтинга

## Примечания
- Бандла нет, все скрипты — отдельные файлы.

## Локальная разработка (env-docker)
env-docker лежит рядом: `/home/sxtim/dev/env-docker`, сайт доступен на `http://127.0.0.1:8588/`.

Запуск/остановка:
```bash
cd /home/sxtim/dev/env-docker
docker compose up -d        # старт
docker compose stop         # стоп
docker compose down         # стоп + удалить контейнеры (тома не трогает)
docker compose logs -f nginx php
```

Восстановление из бэкапа делай без override (чтобы случайно не перетереть код из Git):
```bash
cd /home/sxtim/dev/env-docker
docker compose -f docker-compose.yml up -d
```
Дальше открывай `http://127.0.0.1:8588/restore.php` и восстанавливай архив.

### Новые страницы/папки в корне сайта
Сейчас в `docker-compose.override.yml` прокинуты не весь репозиторий, а только выбранные пути (`local/`, `apartments/`, `consulting/`, `tenders/`, `index.php`).

Если создал новую папку в корне сайта (например `newpage/`) и хочешь видеть её в локалке — добавь её в `/home/sxtim/dev/env-docker/docker-compose.override.yml` в секции `php.volumes` и `nginx.volumes`:
```yml
- ../szkub/newpage:/opt/www/newpage
```
И примени:
```bash
cd /home/sxtim/dev/env-docker
docker compose up -d
```

## Прод: деплой из Git
Прод обновляется без merge (предсказуемо) — трогаются только tracked файлы.

```bash
cd /home/c/cf144342/bitrix_d7dca/public_html
git fetch origin --prune
git reset --hard origin/main
git diff --name-status HEAD..origin/main - посмотреть, что изменится
```

Откат:
```bash
git reset --hard <commit>
```
