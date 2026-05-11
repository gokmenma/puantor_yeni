<div class="modal modal-blur fade" id="projectPersonnelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proje Personel Listesi - <span id="modalProjectName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>TC No</th>
                                <th>Grup</th>
                                <th>Ekip</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="projectPersonnelTableBody">
                            <!-- Personeller buraya yüklenecek -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('click', '.show-project-personnel', function(e) {
    e.preventDefault();
    const projectId = $(this).data('id');
    const projectName = $(this).data('name');
    
    $('#modalProjectName').text(projectName);
    $('#projectPersonnelTableBody').html('<tr><td colspan="5" class="text-center">Yükleniyor...</td></tr>');
    $('#projectPersonnelModal').modal('show');
    
    $.ajax({
        url: 'api/projects/get-personnel.php',
        type: 'GET',
        data: { project_id: projectId },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '';
                if (response.data.length > 0) {
                    response.data.forEach(function(person) {
                        html += `<tr>
                            <td>${person.name} ${person.surname}</td>
                            <td>${person.tckn || '-'}</td>
                            <td>${person.job_group_name || '-'}</td>
                            <td>${person.team_name || '-'}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-ghost-primary route-link" data-page="persons/edit&id=${person.encrypted_id}" data-bs-dismiss="modal">
                                    Profil
                                </a>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="5" class="text-center">Bu projede kayıtlı personel bulunamadı.</td></tr>';
                }
                $('#projectPersonnelTableBody').html(html);
            } else {
                let debugInfo = response.debug ? `<br><small class="text-muted">${response.debug.file} (Line: ${response.debug.line})</small>` : '';
                $('#projectPersonnelTableBody').html(`<tr><td colspan="5" class="text-center text-danger">${response.message}${debugInfo}</td></tr>`);
            }
        },
        error: function() {
            $('#projectPersonnelTableBody').html('<tr><td colspan="5" class="text-center text-danger">Bir hata oluştu.</td></tr>');
        }
    });
});
</script>
