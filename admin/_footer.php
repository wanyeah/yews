<?php
/**
 * 后台公共底部
 */
?>
        </div><!-- /.admin-content -->
    </div><!-- /.admin-main -->
</div><!-- /.admin-wrap -->
<script>
(function () {
    var btn = document.getElementById('navToggle');
    var side = document.getElementById('adminSide');
    if (btn && side) {
        btn.addEventListener('click', function () {
            side.classList.toggle('open');
        });
    }
})();
</script>
</body>
</html>
