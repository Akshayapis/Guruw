</div><!-- adminx-container -->
<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../assets/dist/js/vendor.js"></script>
<script src="../assets/dist/js/adminx.js"></script>
<script src="../assets/dist/js/table2excel.js"></script>
<script src="../assets/dist/js/custom-new.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/js/cashpe.js?v=20261120r3"></script>
<script type="text/javascript">
function Export(str) {
  $(".table").table2excel({ filename: str + ".xls" });
}
function copyToClipboard(element) {
  var $temp = $("<input>");
  $("body").append($temp);
  $temp.val($(element).text()).select();
  document.execCommand("copy");
  $temp.remove();
  if (window.swal) swal("Copied!", "Copied to clipboard", "success");
}
</script>
</body>
</html>
