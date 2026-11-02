<?php if (!$bare): ?>
        </main>
    </div>
<?php endif; ?>
<script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<?php foreach (($scripts ?? []) as $__js): ?>
<script src="<?= e(asset($__js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
