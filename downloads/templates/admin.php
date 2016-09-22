<?php $lng = core::load_lng('dl') ?>
<!-- Заголовок раздела -->
<ul class="title admin">
    <li class="left"><a href="<?= App::cfg()->sys->homeurl ?>/admin"><span class="icn icn-back"></span></a></li>
    <li class="separator"></li>
    <li class="center"><h1><?= $lng['downloads'] ?></h1></li>
    <li class="right"></li>
</ul>
<div class="content form-container">
    <?= $this->form ?>
</div>