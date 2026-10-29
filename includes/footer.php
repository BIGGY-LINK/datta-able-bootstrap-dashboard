    </div>
</main>
<footer class="pc-footer">
    <div class="footer-wrapper container-fluid">
        <div class="row align-items-center">
            <div class="col my-1"><p class="m-0"><?= e(match ($role ?? 'guest') { 'admin' => 'Administration workspace', 'department' => 'Department support workspace', 'student' => 'Student support workspace', default => 'Student support portal' }) ?> &middot; Federal College of Wildlife Management</p></div>
            <div class="col-auto my-1"><span class="footer-note">Support with care. Resolve with purpose.</span></div>
        </div>
    </div>
</footer>
<script src="dist/assets/js/plugins/popper.min.js"></script>
<script src="dist/assets/js/plugins/bootstrap.min.js"></script>
<script src="dist/assets/js/script.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
