</main>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> KasaPro. Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
<!-- includes/footer.php dosyasının sonuna ekleyin -->
<script>
// Chart data
const categoryLabels = <?= json_encode(array_column($categoryStats, 'category_name')) ?>;
const categoryValues = <?= json_encode(array_column($categoryStats, 'total_amount')) ?>;
const months = <?= json_encode(array_column($monthlyData, 'month')) ?>;
const incomeData = <?= json_encode(array_column($monthlyData, 'income')) ?>;
const expenseData = <?= json_encode(array_column($monthlyData, 'expense')) ?>;
</script>