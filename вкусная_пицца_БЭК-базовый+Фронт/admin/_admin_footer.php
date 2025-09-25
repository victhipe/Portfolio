</div><!-- /.container -->
    </main><!-- /.admin-main -->

    <footer class="site-footer admin-footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> <?php echo escape(SITE_NAME); ?> - Панель управления.</p>
        </div>
    </footer>

    <!-- Можно подключить отдельный JS для админки, если нужно -->
    <!-- <script src="<?php echo BASE_URL; ?>js/admin.js?v=<?php echo time(); ?>"></script> -->
</body>
</html>
<?php
// Закрытие соединения с БД
global $conn;
if (isset($conn) && $conn instanceof mysqli) {
   // $conn->close();
}
?>