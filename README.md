# szkub

Краткая инструкция по работе с вёрсткой.

## Структура
- `docs/` — исходная верстка
- `local/templates/szcube/` — шаблон Bitrix (css/js/img/fonts + header/footer)
- `index.php` — главная
- `consulting/` — страница консалтинга

## Примечания
- Бандла нет, все скрипты — отдельные файлы.

## Flow (правка → заливка → проверка → коммит)
### Один раз на сервере
```bash
cd /home/c/cf144342/bitrix_d7dca/public_html
git remote add origin git@github.com:sxtim/szkub.git
git fetch origin
```

### Каждый раз
1) Локально: правки.
2) Заливка на сервер (FTP/SFTP): `local/`, `index.php`, `consulting/`.
3) Проверка на сервере.
4) Если всё ок: `git add -A` → `git commit -m "..."` → `git push`.

### Если не ок
1) Локально: откатить изменения (`git checkout -- .` или `git reset --hard <commit>`).
2) Сервер: вернуть файлы к последней рабочей версии (перезалить по FTP нужные файлы).

## Git-деплой (гибридный режим)
Можно обновлять сервер из GitHub, если удобно.

Обновить сервер:
```bash
cd /home/c/cf144342/bitrix_d7dca/public_html
git pull origin main
```

Откатить сервер к коммиту:
```bash
git reset --hard <commit>
```

Важно: гибридный режим требует дисциплины — после ручной FTP-заливки делай `git commit` и `git push`, иначе `git pull` может перезатереть изменения.
