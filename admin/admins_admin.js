// Admin Management System - Fixed JavaScript
class AdminManager {
    constructor() {
        this.staff = [];
        this.filteredStaff = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.currentEditId = null;
        this.apiBaseUrl = 'http://localhost/steelproject/admin/controllers/';
        this.searchTimeout = null;
        
        this.initializeMappings();
        this.init();
    }

    initializeMappings() {
        // Role mapping with Thai translations
        this.roleMap = {
            'manager': { class: 'role-manager', text: 'ผู้จัดการ', avatar: '#6f42c1' },
            'sales': { class: 'role-sales', text: 'พนักงานขาย', avatar: '#fd7e14' },
            'warehouse': { class: 'role-warehouse', text: 'พนักงานคลัง', avatar: '#007bff' },
            'shipping': { class: 'role-shipping', text: 'พนักงานขนส่ง', avatar: '#28a745' },
            'accounting': { class: 'role-accounting', text: 'พนักงานบัญชี', avatar: '#dc3545' },
            'super': { class: 'role-super', text: 'ผู้ดูแลระบบ', avatar: '#6f42c1' }
        };

        // Department mapping with Thai translations
        this.departmentMap = {
            'management': 'บริหาร',
            'sales': 'ขาย',
            'warehouse': 'คลังสินค้า',
            'logistics': 'ขนส่ง',
            'accounting': 'บัญชี',
            'it': 'เทคโนโลยีสารสนเทศ'
        };
    }

    async init() {
        try {
            await this.loadStaff();
            this.setupEventListeners();
            this.renderStaff();
        } catch (error) {
            console.error('Initialization error:', error);
            this.showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
        }
    }

    // API Methods
    async loadStaff() {
        try {
            const response = await fetch(`${this.apiBaseUrl}get_admin.php`);
            const result = await response.json();
            
            if (result.success) {
                this.staff = Array.isArray(result.data) ? result.data : [];
                this.filteredStaff = [...this.staff];
            } else {
                throw new Error(result.message || 'Failed to load staff');
            }
        } catch (error) {
            console.error('Error loading staff:', error);
            // Fallback to sample data if API fails
            this.loadSampleData();
        }
    }

    loadSampleData() {
        this.staff = [
            {
                admin_id: "EMP1001",
                fullname: "นายวิชัย ผู้จัดการ",
                phone: "089-123-4567",
                position: "manager",
                department: "management",
                status: "active",
                created_at: "2024-01-01 09:00:00"
            },
            {
                admin_id: "EMP2305", 
                fullname: "นางสาวสุดา นักขาย",
                phone: "081-987-6543",
                position: "sales",
                department: "sales", 
                status: "active",
                created_at: "2024-01-15 10:00:00"
            },
            {
                admin_id: "EMP9999",
                fullname: "นายระบบ ผู้ดูแล",
                phone: "080-000-1234", 
                position: "super",
                department: "it",
                status: "active",
                created_at: "2024-01-01 08:00:00"
            }
        ];
        this.filteredStaff = [...this.staff];
    }

    async saveStaff(staffData) {
        try {
            const url = this.currentEditId 
                ? `${this.apiBaseUrl}manage_admin.php`
                : `${this.apiBaseUrl}add_admin.php`;
            
            const method = this.currentEditId ? 'PUT' : 'POST';
            
            if (this.currentEditId) {
                staffData.admin_id = this.currentEditId;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(staffData)
            });

            const result = await response.json();
            
            if (result.success) {
                if (this.currentEditId) {
                    // Update existing staff
                    const index = this.staff.findIndex(s => s.admin_id === this.currentEditId);
                    if (index !== -1) {
                        this.staff[index] = { ...this.staff[index], ...result.data };
                    }
                } else {
                    // Add new staff
                    this.staff.push(result.data);
                }
                
                this.applyFilters();
                return { success: true, message: result.message };
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error saving staff:', error);
            return { success: false, message: error.message };
        }
    }

    async deleteStaff(adminId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}manage_admin.php?admin_id=${adminId}`, {
                method: 'DELETE'
            });

            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid response');
            }

            if (result.success) {
                this.staff = this.staff.filter(s => s.admin_id !== adminId);
                this.applyFilters();
                return { success: true, message: result.message };
            } else {
                throw new Error(result.message);
            }

        } catch (error) {
            console.error('Error deleting staff:', error);
            return { success: false, message: error.message };
        }
    }

    async toggleStaffStatus(adminId) {
        try {
            const staff = this.staff.find(s => s.admin_id === adminId);
            if (!staff) throw new Error('ไม่พบพนักงาน');

            const newStatus = staff.status === 'active' ? 'inactive' : 'active';
            
            const response = await fetch(`${this.apiBaseUrl}manage_admin.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    admin_id: adminId,
                    status: newStatus
                })
            });

            const result = await response.json();
            
            if (result.success) {
                staff.status = newStatus;
                this.applyFilters();
                return { success: true, message: result.message };
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error toggling staff status:', error);
            return { success: false, message: error.message };
        }
    }

    // UI Methods
    setupEventListeners() {
        // Search and filter events
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const departmentFilter = document.getElementById('departmentFilter');
        const statusFilter = document.getElementById('statusFilter');
        const sortFilter = document.getElementById('sortFilter');

        if (searchInput) {
            searchInput.addEventListener('input', this.debounceSearch.bind(this));
        }

        if (roleFilter) {
            roleFilter.addEventListener('change', this.applyFilters.bind(this));
        }

        if (departmentFilter) {
            departmentFilter.addEventListener('change', this.applyFilters.bind(this));
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', this.applyFilters.bind(this));
        }

        if (sortFilter) {
            sortFilter.addEventListener('change', this.onSortChange.bind(this));
        }

        // Form events
        const staffForm = document.getElementById('staffForm');
        if (staffForm) {
            staffForm.addEventListener('submit', this.handleFormSubmit.bind(this));
        }

        const staffRole = document.getElementById('staffRole');
        if (staffRole) {
            staffRole.addEventListener('change', this.updateDepartmentAndPermissions.bind(this));
        }

        const staffDepartment = document.getElementById('staffDepartment');
        if (staffDepartment) {
            staffDepartment.addEventListener('change', this.generateStaffCode.bind(this));
        }

        const passwordInput = document.getElementById('staffPassword');
        if (passwordInput) {
            passwordInput.addEventListener('input', (e) => this.updatePasswordStrength(e.target.value));
        }

        // Modal events
        this.setupModalEvents();
        this.setupKeyboardShortcuts();
    }

    setupModalEvents() {
        const staffModal = document.getElementById('staffModal');
        const staffDetailsModal = document.getElementById('staffDetailsModal');

        if (staffModal) {
            staffModal.addEventListener('click', (e) => {
                if (e.target === staffModal) {
                    this.closeModal();
                }
            });
        }

        if (staffDetailsModal) {
            staffDetailsModal.addEventListener('click', (e) => {
                if (e.target === staffDetailsModal) {
                    this.closeStaffDetailsModal();
                }
            });
        }

        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                this.closeStaffDetailsModal();
            }
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+N or Cmd+N for new staff
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                this.openAddModal();
            }
            
            // Ctrl+F or Cmd+F for search focus
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) searchInput.focus();
            }
        });
    }

    // Form handling
    async handleFormSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }

        const formData = this.getFormData();
        const result = await this.saveStaff(formData);
        
        if (result.success) {
            this.showNotification(result.message, 'success');
            this.closeModal();
        } else {
            this.showNotification(result.message, 'error');
        }
    }

    getFormData() {
        const selectedPermissions = [];
        document.querySelectorAll('#permissionsGrid input[type="checkbox"]:checked').forEach(checkbox => {
            selectedPermissions.push(checkbox.value);
        });

        return {
            admin_id: document.getElementById('staffCode')?.value || '',
            fullname: document.getElementById('staffName')?.value || '',
            phone: document.getElementById('staffPhone')?.value || '',
            position: document.getElementById('staffRole')?.value || '',
            department: document.getElementById('staffDepartment')?.value || '',
            status: document.getElementById('staffActive')?.checked ? 'active' : 'inactive',
            password: document.getElementById('staffPassword')?.value || '',
            permissions: selectedPermissions
        };
    }

    validateForm() {
        const password = document.getElementById('staffPassword')?.value || '';
        const passwordConfirm = document.getElementById('staffPasswordConfirm')?.value || '';
        const fullname = document.getElementById('staffName')?.value || '';

        if (!fullname.trim()) {
            this.showNotification('กรุณาระบุชื่อ-นามสกุล', 'error');
            return false;
        }

        // Check password for new staff only
        if (!this.currentEditId && document.getElementById('passwordGroup')?.style.display !== 'none') {
            if (password.length < 8) {
                this.showNotification('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร', 'error');
                return false;
            }

            if (password !== passwordConfirm) {
                this.showNotification('รหัสผ่านไม่ตรงกัน', 'error');
                return false;
            }
        }

        return true;
    }

    // Password utilities
    generateRandomPassword() {
        const letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const numbers = '0123456789';
        
        let password = '';
        
        // Generate 4 random letters (mixed case)
        for (let i = 0; i < 4; i++) {
            password += letters.charAt(Math.floor(Math.random() * letters.length));
        }
        
        // Generate 4 random numbers
        for (let i = 0; i < 4; i++) {
            password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        }
        
        // Set password in both fields
        const passwordField = document.getElementById('staffPassword');
        const confirmField = document.getElementById('staffPasswordConfirm');
        
        if (passwordField) passwordField.value = password;
        if (confirmField) confirmField.value = password;
        
        // Show generated password display
        const display = document.getElementById('generatedPasswordDisplay');
        const passwordText = document.getElementById('generatedPasswordText');
        
        if (display && passwordText) {
            passwordText.textContent = password;
            display.style.display = 'block';
            display.classList.add('password-generated');
            
            setTimeout(() => {
                display.classList.remove('password-generated');
            }, 300);
        }
        
        this.updatePasswordStrength(password);
        this.showNotification('สร้างรหัสผ่านใหม่แล้ว: ' + password, 'success');
    }

    togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button?.querySelector('i');
        
        if (input && icon) {
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    }

    async copyPassword() {
        const passwordText = document.getElementById('generatedPasswordText')?.textContent;
        if (!passwordText) return;

        try {
            await navigator.clipboard.writeText(passwordText);
            this.showNotification('คัดลอกรหัสผ่านแล้ว', 'success');
        } catch (err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = passwordText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showNotification('คัดลอกรหัสผ่านแล้ว', 'success');
        }
    }

    // Staff code generation - Updated to auto-generate when opening add modal
    generateStaffCode() {
        const staffCodeInput = document.getElementById('staffCode');
        
        if (!staffCodeInput) return;

        // Generate a random 4-digit number
        const randomNumber = Math.floor(1000 + Math.random() * 9000);
        const newCode = `EMP${randomNumber}`;
        
        // Check if this code already exists in the current staff list
        const existingCodes = this.staff.map(s => s.admin_id);
        
        // If code exists, generate a new one (recursive approach with safety limit)
        if (existingCodes.includes(newCode)) {
            let attempts = 0;
            let uniqueCode = newCode;
            
            while (existingCodes.includes(uniqueCode) && attempts < 100) {
                const newRandomNumber = Math.floor(1000 + Math.random() * 9000);
                uniqueCode = `EMP${newRandomNumber}`;
                attempts++;
            }
            
            staffCodeInput.value = uniqueCode;
        } else {
            staffCodeInput.value = newCode;
        }
        
        // Auto-generate notification is optional, remove if too noisy
        // this.showNotification(`สร้างรหัสพนักงานใหม่: ${staffCodeInput.value}`, 'info');
    }

    // Role and department management
    updateDepartmentAndPermissions() {
        const role = document.getElementById('staffRole')?.value;
        const departmentSelect = document.getElementById('staffDepartment');
        const permissionCheckboxes = document.querySelectorAll('#permissionsGrid input[type="checkbox"]');
        
        if (!role) return;

        // Clear all permissions first
        permissionCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.disabled = false;
        });

        // Set department and permissions based on role
        const roleConfigs = {
            'manager': {
                department: 'management',
                permissions: ['dashboard', 'products', 'orders', 'admins', 'reports']
            },
            'sales': {
                department: 'sales',
                permissions: ['dashboard', 'products', 'orders']
            },
            'warehouse': {
                department: 'warehouse', 
                permissions: ['dashboard', 'products']
            },
            'shipping': {
                department: 'logistics',
                permissions: ['dashboard', 'orders']
            },
            'accounting': {
                department: 'accounting',
                permissions: ['dashboard', 'orders', 'reports']
            },
            'super': {
                department: 'it',
                permissions: ['dashboard', 'products', 'orders', 'admins', 'reports'],
                lockPermissions: true
            }
        };

        const config = roleConfigs[role];
        if (config && departmentSelect) {
            departmentSelect.value = config.department;
            
            config.permissions.forEach(perm => {
                const checkbox = document.getElementById(`perm-${perm}`);
                if (checkbox) {
                    checkbox.checked = true;
                    if (config.lockPermissions) {
                        checkbox.disabled = true;
                    }
                }
            });
        }
        
        // Generate staff code when role changes (if it's for new staff)
        if (!this.currentEditId) {
            this.generateStaffCode();
        }
    }

    // Utility methods
    getRoleInfo(role) {
        return this.roleMap[role] || { class: 'role-manager', text: 'ไม่ทราบ', avatar: '#6c757d' };
    }

    getStatusInfo(status) {
        const statusMap = {
            'active': { class: 'status-active', text: 'ใช้งานอยู่' },
            'inactive': { class: 'status-inactive', text: 'ไม่ได้ใช้งาน' }
        };
        return statusMap[status] || { class: 'status-inactive', text: 'ไม่ทราบ' };
    }

    getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    }

    // Filtering and searching
    debounceSearch() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => this.applyFilters(), 300);
    }

    applyFilters() {
        const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const roleFilter = document.getElementById('roleFilter')?.value || '';
        const departmentFilter = document.getElementById('departmentFilter')?.value || '';
        const statusFilter = document.getElementById('statusFilter')?.value || '';

        this.filteredStaff = this.staff.filter(person => {
            const matchesSearch = person.fullname.toLowerCase().includes(searchTerm) ||
                                this.getRoleInfo(person.position).text.toLowerCase().includes(searchTerm) ||
                                (person.admin_id && person.admin_id.toLowerCase().includes(searchTerm));
            
            const matchesRole = !roleFilter || person.position === roleFilter;
            const matchesDepartment = !departmentFilter || person.department === departmentFilter;
            const matchesStatus = !statusFilter || person.status === statusFilter;

            return matchesSearch && matchesRole && matchesDepartment && matchesStatus;
        });

        this.applySorting();
        this.currentPage = 1;
        this.renderStaff();
    }

    applySorting() {
        const sortBy = document.getElementById('sortFilter')?.value || 'name';
        
        this.filteredStaff.sort((a, b) => {
            switch (sortBy) {
                case 'name':
                    return a.fullname.localeCompare(b.fullname, 'th');
                case 'role':
                    return a.position.localeCompare(b.position);
                case 'department':
                    return a.department.localeCompare(b.department);
                case 'created':
                    return new Date(b.created_at) - new Date(a.created_at);
                default:
                    return 0;
            }
        });
    }

    onSortChange() {
        this.applySorting();
        this.renderStaff();
    }

    // Modal methods
    openAddModal() {
        const modalTitle = document.getElementById('modalTitle');
        const staffForm = document.getElementById('staffForm');
        const staffActive = document.getElementById('staffActive');
        const passwordGroup = document.getElementById('passwordGroup');
        const passwordConfirmGroup = document.getElementById('passwordConfirmGroup');
        const generatedPasswordDisplay = document.getElementById('generatedPasswordDisplay');
        const passwordStrength = document.getElementById('passwordStrength');
        const staffCode = document.getElementById('staffCode');
        const permissionCheckboxes = document.querySelectorAll('#permissionsGrid input[type="checkbox"]');
        
        if (modalTitle) modalTitle.textContent = 'เพิ่มพนักงานใหม่';
        if (staffForm) staffForm.reset();
        if (staffActive) staffActive.checked = true;
        
        this.currentEditId = null;
        
        // Show password fields for new staff
        if (passwordGroup) passwordGroup.style.display = 'block';
        if (passwordConfirmGroup) passwordConfirmGroup.style.display = 'block';
        
        const passwordField = document.getElementById('staffPassword');
        const passwordConfirmField = document.getElementById('staffPasswordConfirm');
        if (passwordField) passwordField.required = true;
        if (passwordConfirmField) passwordConfirmField.required = true;
        
        // Hide generated password display
        if (generatedPasswordDisplay) generatedPasswordDisplay.style.display = 'none';
        if (passwordStrength) passwordStrength.style.display = 'none';
        
        // Clear and generate new staff code immediately
        if (staffCode) staffCode.value = '';
        this.generateStaffCode(); // Generate staff code right when opening modal
        
        // Clear permissions
        permissionCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.disabled = false;
        });
        
        // Set default permissions (will also trigger staff code generation, but that's ok)
        this.updateDepartmentAndPermissions();
        
        const staffModal = document.getElementById('staffModal');
        if (staffModal) staffModal.style.display = 'block';
    }

    closeModal() {
        const staffModal = document.getElementById('staffModal');
        if (staffModal) staffModal.style.display = 'none';
        this.currentEditId = null;
    }

    closeStaffDetailsModal() {
        const staffDetailsModal = document.getElementById('staffDetailsModal');
        if (staffDetailsModal) staffDetailsModal.style.display = 'none';
    }

    // Staff CRUD operations
    viewStaffDetails(adminId) {
        const person = this.staff.find(s => s.admin_id === adminId);
        if (!person) return;

        const roleInfo = this.getRoleInfo(person.position);
        const statusInfo = this.getStatusInfo(person.status);
        const departmentName = this.departmentMap[person.department] || person.department;

        // Populate staff details
        const elements = {
            title: document.getElementById('staffDetailsTitle'),
            name: document.getElementById('detailName'),
            phone: document.getElementById('detailPhone'),
            code: document.getElementById('detailCode'),
            role: document.getElementById('detailRole'),
            department: document.getElementById('detailDepartment'),
            status: document.getElementById('detailStatus'),
            notes: document.getElementById('detailNotes'),
            permissions: document.getElementById('detailPermissions')
        };

        if (elements.title) elements.title.textContent = `รายละเอียดพนักงาน - ${person.fullname}`;
        if (elements.name) elements.name.textContent = person.fullname;
        if (elements.phone) elements.phone.textContent = person.phone || 'ไม่ระบุ';
        if (elements.code) elements.code.textContent = person.admin_id || 'ไม่ระบุ';
        if (elements.role) elements.role.innerHTML = `<span class="role-badge ${roleInfo.class}">${roleInfo.text}</span>`;
        if (elements.department) elements.department.textContent = departmentName;
        if (elements.status) elements.status.innerHTML = `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
        if (elements.notes) elements.notes.textContent = person.notes || 'ไม่มีหมายเหตุ';

        // Populate permissions
        if (elements.permissions) {
            const permissionLabels = {
                dashboard: 'แดชบอร์ด',
                products: 'จัดการสินค้า',
                orders: 'จัดการคำสั่งซื้อ',
                admins: 'จัดการผู้ดูแล',
                reports: 'รายงาน'
            };

            const permissions = person.permissions || this.getDefaultPermissions(person.position);
            elements.permissions.innerHTML = permissions.map(perm => `
                <div style="padding: 8px; background: white; border-radius: 6px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check" style="color: #28a745;"></i>
                    <span>${permissionLabels[perm] || perm}</span>
                </div>
            `).join('');
        }

        const staffDetailsModal = document.getElementById('staffDetailsModal');
        if (staffDetailsModal) staffDetailsModal.style.display = 'block';
    }

    getDefaultPermissions(position) {
        const defaultPermissions = {
            'manager': ['dashboard', 'products', 'orders', 'admins', 'reports'],
            'sales': ['dashboard', 'products', 'orders'],
            'warehouse': ['dashboard', 'products'],
            'shipping': ['dashboard', 'orders'],
            'accounting': ['dashboard', 'orders', 'reports'],
            'super': ['dashboard', 'products', 'orders', 'admins', 'reports']
        };
        return defaultPermissions[position] || ['dashboard'];
    }

    editStaff(adminId) {
        const person = this.staff.find(s => s.admin_id === adminId);
        if (!person) return;

        this.currentEditId = adminId;
        
        const elements = {
            modalTitle: document.getElementById('modalTitle'),
            staffName: document.getElementById('staffName'),
            staffPhone: document.getElementById('staffPhone'),
            staffCode: document.getElementById('staffCode'),
            staffRole: document.getElementById('staffRole'),
            staffDepartment: document.getElementById('staffDepartment'),
            staffActive: document.getElementById('staffActive'),
            staffNotes: document.getElementById('staffNotes'),
            passwordGroup: document.getElementById('passwordGroup'),
            passwordConfirmGroup: document.getElementById('passwordConfirmGroup'),
            staffPassword: document.getElementById('staffPassword'),
            staffPasswordConfirm: document.getElementById('staffPasswordConfirm')
        };

        if (elements.modalTitle) elements.modalTitle.textContent = 'แก้ไขข้อมูลพนักงาน';
        if (elements.staffName) elements.staffName.value = person.fullname;
        if (elements.staffPhone) elements.staffPhone.value = person.phone || '';
        if (elements.staffCode) elements.staffCode.value = person.admin_id || '';
        if (elements.staffRole) elements.staffRole.value = person.position;
        if (elements.staffDepartment) elements.staffDepartment.value = person.department;
        if (elements.staffActive) elements.staffActive.checked = person.status === 'active';
        if (elements.staffNotes) elements.staffNotes.value = person.notes || '';
        
        // Hide password fields for editing
        if (elements.passwordGroup) elements.passwordGroup.style.display = 'none';
        if (elements.passwordConfirmGroup) elements.passwordConfirmGroup.style.display = 'none';
        if (elements.staffPassword) elements.staffPassword.required = false;
        if (elements.staffPasswordConfirm) elements.staffPasswordConfirm.required = false;

        // Set permissions
        this.updateDepartmentAndPermissions();
        const permissions = person.permissions || this.getDefaultPermissions(person.position);
        permissions.forEach(perm => {
            const checkbox = document.getElementById(`perm-${perm}`);
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = true;
            }
        });

        const staffModal = document.getElementById('staffModal');
        if (staffModal) staffModal.style.display = 'block';
    }

    async toggleStatus(adminId) {
        const person = this.staff.find(s => s.admin_id === adminId);
        if (!person) return;

        const newStatus = person.status === 'active' ? 'inactive' : 'active';
        const statusText = newStatus === 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        
        if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการ${statusText} "${person.fullname}"?`)) {
            const result = await this.toggleStaffStatus(adminId);
            if (result.success) {
                this.showNotification(result.message, 'success');
            } else {
                this.showNotification(result.message, 'error');
            }
        }
    }

    async confirmDelete(adminId) {
        const person = this.staff.find(s => s.admin_id === adminId);
        if (!person) return;
        
        if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ "${person.fullname}"?\n\nการกระทำนี้ไม่สามารถย้อนกลับได้`)) {
            const result = await this.deleteStaff(adminId);
            if (result.success) {
                this.showNotification(result.message, 'success');
            } else {
                this.showNotification(result.message, 'error');
            }
        }
    }

    // Rendering methods
    renderStaff() {
        const tbody = document.getElementById('staffTableBody');
        if (!tbody) return;

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const pageStaff = this.filteredStaff.slice(startIndex, endIndex);

        tbody.innerHTML = pageStaff.map(person => {
            const roleInfo = this.getRoleInfo(person.position);
            const statusInfo = this.getStatusInfo(person.status);
            const initials = this.getInitials(person.fullname);
            const departmentName = this.departmentMap[person.department] || person.department;
            
            return `
                <tr>
                    <td>
                        <div class="staff-info">
                            <div class="staff-avatar" style="background: linear-gradient(45deg, ${roleInfo.avatar}, ${roleInfo.avatar}90)">
                                ${initials}
                            </div>
                            <div class="staff-details">
                                <div class="staff-name">${person.fullname}</div>
                                <div class="staff-phone">${person.phone || 'ไม่ระบุ'}</div>
                                <div class="staff-department">${person.admin_id}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge ${roleInfo.class}">${roleInfo.text}</span>
                    </td>
                    <td>
                        <span style="color: #666; font-weight: 500;">${departmentName}</span>
                    </td>
                    <td>
                        <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </td>
                    <td class="actions">
                        <button class="view-btn" onclick="adminManager.viewStaffDetails('${person.admin_id}')" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="edit-btn" onclick="adminManager.editStaff('${person.admin_id}')" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="toggle-btn" onclick="adminManager.toggleStatus('${person.admin_id}')" title="${person.status === 'active' ? 'ปิดใช้งาน' : 'เปิดใช้งาน'}">
                            <i class="fas fa-${person.status === 'active' ? 'ban' : 'check'}"></i>
                        </button>
                        ${person.position !== 'manager' && person.position !== 'super' ? `
                            <button class="delete-btn" onclick="adminManager.confirmDelete('${person.admin_id}')" title="ลบ">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
        }).join('');

        this.renderPagination();
        this.updateStats();
    }

    renderPagination() {
        const totalPages = Math.ceil(this.filteredStaff.length / this.itemsPerPage);
        const pagination = document.getElementById('pagination');
        
        if (!pagination || totalPages <= 1) {
            if (pagination) pagination.innerHTML = '';
            return;
        }

        let buttons = [];
        
        // Previous button
        buttons.push(`
            <button onclick="adminManager.changePage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            buttons.push(`
                <button onclick="adminManager.changePage(${i})" ${i === this.currentPage ? 'class="active"' : ''}>
                    ${i}
                </button>
            `);
        }

        // Next button
        buttons.push(`
            <button onclick="adminManager.changePage(${this.currentPage + 1})" ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `);

        pagination.innerHTML = buttons.join('');
    }

    changePage(page) {
        const totalPages = Math.ceil(this.filteredStaff.length / this.itemsPerPage);
        if (page >= 1 && page <= totalPages) {
            this.currentPage = page;
            this.renderStaff();
        }
    }

    updateStats() {
        const stats = {
            total: this.filteredStaff.length,
            active: this.filteredStaff.filter(s => s.status === 'active').length,
            manager: this.filteredStaff.filter(s => s.position === 'manager').length,
            sales: this.filteredStaff.filter(s => s.position === 'sales').length,
            warehouse: this.filteredStaff.filter(s => s.position === 'warehouse').length
        };

        const elements = {
            totalStaff: document.getElementById('totalStaff'),
            activeStaff: document.getElementById('activeStaff'),
            managerCount: document.getElementById('managerCount'),
            salesCount: document.getElementById('salesCount'),
            warehouseCount: document.getElementById('warehouseCount')
        };

        if (elements.totalStaff) elements.totalStaff.textContent = stats.total;
        if (elements.activeStaff) elements.activeStaff.textContent = stats.active;
        if (elements.managerCount) elements.managerCount.textContent = stats.manager;
        if (elements.salesCount) elements.salesCount.textContent = stats.sales;
        if (elements.warehouseCount) elements.warehouseCount.textContent = stats.warehouse;
    }

    // Notification system
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 3000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 400px;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            ${type === 'success' ? 'background: linear-gradient(45deg, #28a745, #20c997);' : ''}
            ${type === 'error' ? 'background: linear-gradient(45deg, #dc3545, #c82333);' : ''}
            ${type === 'info' ? 'background: linear-gradient(45deg, #17a2b8, #20c997);' : ''}
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Hide notification
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    // Sidebar and navigation
    toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const main = document.querySelector(".main-content");

        if (sidebar) {
            sidebar.classList.toggle("show");
            if (main) main.classList.toggle("overlay");
        }
    }

    showSection(section) {
        const pages = {
            'dashboard': 'dashboard_admin.html',
            'products': 'products_admin.html', 
            'orders': 'orders_admin.html',
            'reports': 'reports_admin.html',
            'admins': null // Current page
        };

        if (pages[section] && pages[section] !== null) {
            window.location.href = pages[section];
        } else if (section === 'admins') {
            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById("sidebar");
                const main = document.querySelector(".main-content");
                if (sidebar) {
                    sidebar.classList.remove("show");
                    if (main) main.classList.remove("overlay");
                }
            }
        }
    }
}

// Initialize the admin manager when DOM is loaded
let adminManager;

document.addEventListener('DOMContentLoaded', function() {
    adminManager = new AdminManager();
    
    // Setup global click handler for sidebar
    document.addEventListener("click", function (e) {
        const sidebar = document.getElementById("sidebar");
        const toggle = document.querySelector(".navbar-toggle");
        const main = document.querySelector(".main-content");

        if (!sidebar || !main) return;

        const clickedOutside = !sidebar.contains(e.target) && 
                              (!toggle || !toggle.contains(e.target));

        if (sidebar.classList.contains("show") && clickedOutside && window.innerWidth <= 768) {
            sidebar.classList.remove("show");
            main.classList.remove("overlay");
        }
    });
});

// Global functions for backward compatibility
function openAddModal() {
    if (adminManager) adminManager.openAddModal();
}

function closeModal() {
    if (adminManager) adminManager.closeModal();
}

function closeStaffDetailsModal() {
    if (adminManager) adminManager.closeStaffDetailsModal();
}

function generateRandomPassword() {
    if (adminManager) adminManager.generateRandomPassword();
}

function togglePasswordVisibility(inputId, button) {
    if (adminManager) adminManager.togglePasswordVisibility(inputId, button);
}

function copyPassword() {
    if (adminManager) adminManager.copyPassword();
}

function generateStaffCode() {
    if (adminManager) adminManager.generateStaffCode();
}

function updateDepartmentAndPermissions() {
    if (adminManager) adminManager.updateDepartmentAndPermissions();
}

function toggleSidebar() {
    if (adminManager) adminManager.toggleSidebar();
}

function showSection(section) {
    if (adminManager) adminManager.showSection(section);
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminManager;
}