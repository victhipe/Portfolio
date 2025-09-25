</div><!-- /.container -->
    </main><!-- /.operator-main -->

    <footer class="site-footer operator-footer"> <!-- Класс можно сделать operator-footer -->
        <div class="container">
            <p>© <?php echo date('Y'); ?> <?php echo escape(SITE_NAME); ?> - Панель оператора.</p>
        </div>
    </footer>

    <!-- Общие скрипты сайта, если нужны -->
    <script src="<?php echo BASE_URL; ?>js/script.js?v=<?php echo time(); ?>"></script>
    <!-- Специфичные скрипты для операторской, если появятся -->
    <!-- <script src="<?php echo BASE_URL; ?>js/operator.js?v=<?php echo time(); ?>"></script> -->
</body>
</html>
<?php
// Закрытие соединения с БД (опционально, как и в других футерах)
global $conn;
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
   // $conn->close(); // Раскомментировать при необходимости явного закрытия
}
?>