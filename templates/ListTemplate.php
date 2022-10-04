<?php

use App\Controller\DefaultController;
use App\Controller\ListController;

?>
<!DOCTYPE html>
<html lang="">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx"
          crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" referrerpolicy="origin"
          href="https://rawcdn.githack.com/shopware/administration/v6.4.13.0/Resources/public/static/css/app.css">
    <style>
        body {
            overflow-y: scroll;
        }
        .list{
            height: 100%;
        }
        @media (min-width: 400px) {
            .col-ss{
                flex: 0 0 auto;
                width: 50%;
            }
        }
    </style>
    <title></title>
</head>
<body class="py-4 pr-4">

    <div class="row mb-2">
        <div class="col-12 col-ss">
            <h2>Medien (<?= htmlspecialchars( $result['total'] ?? ''); ?>)</h2>
        </div>
        <div class="col">
            <form method="post">
                <input id="search" class="w-100 p-2" type="text" value="<?= htmlspecialchars($search ?? ''); ?>"
                       name="search" placeholder="Suchen..." onblur="this.form.submit()">
            </form>
        </div>
    </div>

    <div class="list">
        <div class="row row-cols-2 row-cols-lg-6 g-4">
            <?= DefaultController::renderFunction('List', [
                    $result['data'] ?? [],
                    $pageSize ?? ListController::DEFAULT_PAGE_SIZE
                ]); ?>
        </div>
        <div class="loader show"></div>
    </div>

    <script>
        let listUrl = <?= json_encode($listUrl ?? null); ?>;
        const listEl = document.querySelector('.list');
        const loader = document.querySelector('.list .loader');
        let timer;

        const deferList  = async () => {
            loader.classList.add('show');
            clearTimeout(timer);
            timer = setTimeout(async function () {
                try {
                    const template = listEl.querySelector('template');
                    if (template) {
                        renderList(template.innerHTML);
                        template.remove();
                    } else if (listUrl && listUrl.length) {
                        const response = await getList();
                        listUrl = response.listUrl;
                        if (response.data) {
                            renderList(response.data);
                        } else {
                            listUrl = null;
                        }
                    }
                } catch (error) {
                    console.log(error.message);
                } finally {
                    loader.classList.remove('show');
                }
            }, 100);
        };
        document.body.addEventListener('scroll', () => {
            const {
                scrollTop,
                scrollHeight,
                clientHeight
            } = document.documentElement;

            if (scrollTop + clientHeight >= scrollHeight - 5) {
                deferList();
            }
        });
        const getList = async () => {
            const response = await fetch(listUrl);
            if (!response.ok) {
                throw new Error(`An error occurred: ${response.status}`);
            }
            return await response.json();
        }
        const renderList = (data) => {
            const quoteEl = document.createElement('div');
            quoteEl.innerHTML = data;
            quoteEl.classList = 'row row-cols-2 row-cols-lg-6 g-4 mt-2';
            listEl.appendChild(quoteEl);
        };
        const openMedia = (el) => {
            window.parent.postMessage({
                url: el.href,
                alt: el.dataset.alt,
                title: el.title,
                text: el.innerText
            });
            return false;
        };
        loader.classList.remove('show');
    </script>
</body>
</html>