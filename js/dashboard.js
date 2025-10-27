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
            document.getElementById
