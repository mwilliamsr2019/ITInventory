// Global variables
let currentUser = null;
let editingId = null;

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    loadInventory();
    loadLocations();
    setupEventListeners();
});

function checkAuth() {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    
    if (!token || !user) {
        window.location.href = 'index.php';
        return;
    }
    
    currentUser = JSON.parse(user);
    document.getElementById('profileUsername').value = currentUser.username;
    document.getElementById('profileEmail').value = currentUser.email;
}

function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchInventory();
        }
    });
    
    document.getElementById('profileForm').addEventListener('submit', updateProfile);
}

function showSection(section) {
    // Hide all sections
    document.getElementById('inventorySection').style.display = 'none';
    document.getElementById('usersSection').style.display = 'none';
    document.getElementById('profileSection').style.display = 'none';
    
    // Show selected section
    document.getElementById(section + 'Section').style.display = 'block';
    
    // Update active nav
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');
    
    // Load section data
    if (section === 'inventory') {
        loadInventory();
    } else if (section === 'users') {
        loadUsers();
    }
}

function loadInventory(search = '') {
    fetch(`api/inventory.php?search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('inventoryTableBody');
            tbody.innerHTML = '';
            
            data.forEach(item => {
                const row = `
                    <tr>
                        <td>${item.id}</td>
                        <td>${item.make}</td>
                        <td>${item.model}</td>
                        <td>${item.serial_number}</td>
                        <td>${item.property_number}</td>
                        <td>${item.warranty_end_date || ''}</td>
                        <td>${item.excess_date || ''}</td>
                        <td>${item.use_case}</td>
                        <td>${item.location_name}</td>
                        <td>${item.on_site}</td>
                        <td>${item.description || ''}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editItem(${item.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteItem(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        })
        .catch(error => {
            console.error('Error loading inventory:', error);
            alert('Error loading inventory data');
        });
}

function loadLocations() {
    fetch('api/locations.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('location');
            select.innerHTML = '<option value="">Select Location</option>';
            
            data.forEach(location => {
                select.innerHTML += `<option value="${location.id}">${location.location_name}</option>`;
            });
        })
        .catch(error => {
            console.error('Error loading locations:', error);
        });
}

function searchInventory() {
    const search = document.getElementById('searchInput').value;
    loadInventory(search);
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    loadInventory();
}

function editItem(id) {
    fetch(`api/inventory.php?id=${id}`)
        .then(response => response.json())
        .then(item => {
            editingId = id;
            
            document.getElementById('make').value = item.make;
            document.getElementById('model').value = item.model;
            document.getElementById('serialNumber').value = item.serial_number;
            document.getElementById('propertyNumber').value = item.property_number;
            document.getElementById('warrantyEndDate').value = item.warranty_end_date || '';
            document.getElementById('excessDate').value = item.excess_date || '';
            document.getElementById('useCase').value = item.use_case;
            document.getElementById('location').value = item.location_id;
            document.getElementById('onSite').value = item.on_site;
            document.getElementById('description').value = item.description || '';
            
            document.querySelector('#addModal .modal-title').textContent = 'Edit Inventory Item';
            const modal = new bootstrap.Modal(document.getElementById('addModal'));
            modal.show();
        });
}

function saveInventoryItem() {
    const formData = {
        make: document.getElementById('make').value,
        model: document.getElementById('model').value,
        serial_number: document.getElementById('serialNumber').value,
        property_number: document.getElementById('propertyNumber').value,
        warranty_end_date: document.getElementById('warrantyEndDate').value || null,
        excess_date: document.getElementById('excessDate').value || null,
        use_case: document.getElementById('useCase').value,
        location_id: parseInt(document.getElementById('location').value),
        on_site: document.getElementById('onSite').value,
        description: document.getElementById('description').value,
        created_by: currentUser.id
    };
    
    if (editingId) {
        formData.id = editingId;
    }
    
    const method = editingId ? 'PUT' : 'POST';
    const url = 'api/inventory.php';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (response.ok) {
            alert(data.message);
            loadInventory();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
            modal.hide();
            resetForm();
        } else {
            alert(data.message || 'Error saving item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving inventory item');
    });
}

function deleteItem(id) {
    if (confirm('Are you sure you want to delete this item?')) {
        fetch('api/inventory.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            loadInventory();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting item');
        });
    }
}

function resetForm() {
    editingId = null;
    document.getElementById('inventoryForm').reset();
    document.querySelector('#addModal .modal-title').textContent = 'Add Inventory Item';
}

function exportCSV() {
    window.location.href = 'api/csv_handler.php';
}

function importCSV() {
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a CSV file');
        return;
    }
    
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('user_id', currentUser.id);
    
    fetch('api/csv_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(`Import completed. ${data.imported} items imported.`);
        if (data.errors.length > 0) {
            alert('Errors:\n' + data.errors.join('\n'));
        }
        loadInventory();
        const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
        modal.hide();
        document.getElementById('importForm').reset();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error importing CSV');
    });
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'index.php';
}

function updateProfile(e) {
    e.preventDefault();
    
    const email = document.getElementById('profileEmail').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword && newPassword !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }
    
    // Update profile logic would go here
    alert('Profile updated successfully');
}

// Add new location from form
function addNewLocation() {
    const locationName = prompt('Enter new location name:');
    if (locationName) {
        fetch('api/locations.php', {
            method: 'POST',
                       headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ location_name: locationName, description: 'Added from dashboard' })
        })
        .then(response => response.json())
        .then(data => {
            if (response.ok) {
                loadLocations();
                alert('Location added successfully');
            } else {
                alert(data.message || 'Error adding location');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding location');
        });
    }
}

// User Management Functions
function showAddUserModal() {
    // Reset form
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.querySelector('#userModal .modal-title').textContent = 'Add New User';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

function loadUsers() {
    fetch('api/users.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            
            data.forEach(user => {
                const row = `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.username}</td>
                        <td>${user.email}</td>
                        <td>${user.role}</td>
                        <td>${user.auth_type}</td>
                        <td>${user.active ? 'Yes' : 'No'}</td>
                        <td>${new Date(user.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        })
        .catch(error => {
            console.error('Error loading users:', error);
            alert('Error loading user data');
        });
}

function editUser(id) {
    fetch(`api/users.php?id=${id}`)
        .then(response => response.json())
        .then(user => {
            document.getElementById('userId').value = user.id;
            document.getElementById('userUsername').value = user.username;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userActive').value = user.active ? '1' : '0';
            
            document.querySelector('#userModal .modal-title').textContent = 'Edit User';
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error loading user:', error);
            alert('Error loading user data');
        });
}

function saveUser() {
    const userId = document.getElementById('userId').value;
    const formData = {
        username: document.getElementById('userUsername').value,
        email: document.getElementById('userEmail').value,
        role: document.getElementById('userRole').value,
        active: document.getElementById('userActive').value === '1',
        password: document.getElementById('userPassword').value
    };
    
    if (userId) {
        formData.id = userId;
    }
    
    const method = userId ? 'PUT' : 'POST';
    
    fetch('api/users.php', {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (response.ok) {
            alert(data.message);
            loadUsers();
            const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
            modal.hide();
            document.getElementById('userForm').reset();
        } else {
            alert(data.message || 'Error saving user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving user');
    });
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        fetch('api/users.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        })
        .then(response => response.json())
        .then(data => {
                       alert(data.message);
            loadUsers();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting user');
        });
    }
}
            alert(data.message);
            loadUsers();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting user');
        });
    }
}
            alert(data.message);
            loadUsers();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting user');
        });
    }
}
