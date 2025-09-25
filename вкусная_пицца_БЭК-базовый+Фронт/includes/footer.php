</div><!-- /.container -->
    </main><!-- /.site-main -->

    <footer class="site-footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> <?php echo escape(SITE_NAME); ?>. Все права защищены .</p>
            <p>Вкусная пицца</p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>js/script.js?v=<?php echo time(); ?>"></script>
    <!-- Дополнительные скрипты для конкретной страницы -->
     <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js_file): ?>
            <script src="<?php echo BASE_URL . 'js/' . $js_file . '?v=' . time(); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
<?php
// Закрытие соединения с БД, если оно еще открыто
global $conn;
if (isset($conn) && $conn instanceof mysqli) {
   // $conn->close(); // Закрывать или нет - зависит от конфигурации PHP (по умолчанию закрывается само)
}
?>