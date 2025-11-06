// ========================
// ADMIN MANAGEMENT SYSTEM
// ========================

class AdminManager {
    constructor() {
        this.staff = [];
        this.filteredStaff = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.currentEditId = null;
        this.apiBaseUrl = 'http://localhost/steelproject/admin/controllers/';
        
        this.initializeMappings();
        this.init();
    }

    // ========================
    // INITIALIZATION
    // ========================

    // FUNCTION: เริ่มต้นการแมปข้อมูล (บทบาท, แผนก)
    initializeMappings() {
        this.roleMap = {
            'manager': { class: 'role-manager', text: 'ผู้จัดการ', avatar: '#6f42c1' },
            'sales': { class: 'role-sales', text: 'พนักงานขาย', avatar: '#fd7e14' },
            'warehouse': { class: 'role-warehouse', text: 'พนักงานคลัง', avatar: '#007bff' },
            'shipping': { class: 'role-shipping', text: 'พนักงานขนส่ง', avatar: '#28a745' },
            'accounting': { class: 'role-accounting', text: 'พนักงานบัญชี', avatar: '#dc3545' },
            'super': { class: 'role-super', text: 'ผู้ดูแลระบบ', avatar: '#e83e8c' }
        };

        this.departmentMap = {
            'management': 'บริหาร',
            'sales': 'ขาย',
            'warehouse': 'คลังสินค้า',
            'logistics': 'ขนส่ง',
            'accounting': 'บัญชี',
            'it': 'เทคโนโลยีสารสนเทศ'
        };
    }

    // FUNCTION: เริ่มต้นระบบ
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

    // ========================
    // API METHODS
    // ========================

    // FUNCTION: โหลดข้อมูลพนักงานจาก API
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
        }
    }

    // FUNCTION: บันทึกข้อมูลพนักงาน (สร้างหรืออัปเดต)
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(staffData)
            });

            const result = await response.json();
            
            if (result.success) {
                if (this.currentEditId) {
                    const index = this.staff.findIndex(s => s.admin_id === this.currentEditId);
                    if (index !== -1) {
                        this.staff[index] = { ...this.staff[index], ...result.data };
                    }
                } else {
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

    // FUNCTION: ลบข้อมูลพนักงาน
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

    // FUNCTION: สลับสถานะพนักงาน (ใช้งาน/ไม่ใช้งาน)
    async toggleStaffStatus(adminId) {
        try {
            const staff = this.staff.find(s => s.admin_id === adminId);
            if (!staff) throw new Error('ไม่พบพนักงาน');

            const newStatus = staff.status === 'active' ? 'inactive' : 'active';
            
            const response = await fetch(`${this.apiBaseUrl}manage_admin.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
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

    // ========================
    // EVENT LISTENERS
    // ========================

    // FUNCTION: ตั้งค่า Event Listeners สำหรับการค้นหา/กรอง
    setupEventListeners() {
        const elements = {
            searchInput: document.getElementById('searchInput'),
            searchBtn: document.getElementById('searchBtn'),
            resetBtn: document.getElementById('resetBtn'),
            roleFilter: document.getElementById('roleFilter'),
            departmentFilter: document.getElementById('departmentFilter'),
            statusFilter: document.getElementById('statusFilter'),
            sortFilter: document.getElementById('sortFilter'),
            staffForm: document.getElementById('staffForm'),
            staffRole: document.getElementById('staffRole'),
            staffDepartment: document.getElementById('staffDepartment'),
            passwordInput: document.getElementById('staffPassword')
        };

        if (elements.searchBtn) {
            elements.searchBtn.addEventListener('click', this.applyFilters.bind(this));
        }

        if (elements.resetBtn) {
            elements.resetBtn.addEventListener('click', this.resetFilters.bind(this));
        }

        if (elements.sortFilter) {
            elements.sortFilter.addEventListener('change', this.onSortChange.bind(this));
        }

        if (elements.staffForm) {
            elements.staffForm.addEventListener('submit', this.handleFormSubmit.bind(this));
        }

        if (elements.staffRole) {
            elements.staffRole.addEventListener('change', this.updateDepartmentAndPermissions.bind(this));
        }

        if (elements.staffDepartment) {
            elements.staffDepartment.addEventListener('change', this.generateStaffCode.bind(this));
        }

        if (elements.passwordInput) {
            elements.passwordInput.addEventListener('input', (e) => this.updatePasswordStrength(e.target.value));
        }

        if (elements.searchInput) {
            elements.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.applyFilters();
                }
            });
        }

        this.setupModalEvents();
        this.setupKeyboardShortcuts();
    }

    // FUNCTION: ตั้งค่า Events สำหรับโมดัล
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

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                this.closeStaffDetailsModal();
            }
        });
    }

    // FUNCTION: ตั้งค่าปุ่มลัดแป้นพิมพ์
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                this.openAddModal();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) searchInput.focus();
            }
        });
    }

    // ========================
    // FORM HANDLING
    // ========================

    // FUNCTION: จัดการการส่งแบบฟอร์ม
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

    // FUNCTION: ดึงข้อมูลจากแบบฟอร์ม
    getFormData() {
        return {
            admin_id: document.getElementById('staffCode')?.value || '',
            fullname: document.getElementById('staffName')?.value || '',
            phone: document.getElementById('staffPhone')?.value || '',
            position: document.getElementById('staffRole')?.value || '',
            department: document.getElementById('staffDepartment')?.value || '',
            status: document.getElementById('staffActive')?.checked ? 'active' : 'inactive',
            password: document.getElementById('staffPassword')?.value || ''
        };
    }

    // FUNCTION: ตรวจสอบความถูกต้องของแบบฟอร์ม
    validateForm() {
        const password = document.getElementById('staffPassword')?.value || '';
        const passwordConfirm = document.getElementById('staffPasswordConfirm')?.value || '';
        const fullname = document.getElementById('staffName')?.value || '';

        if (!fullname.trim()) {
            this.showNotification('กรุณาระบุชื่อ-สกุล', 'error');
            return false;
        }

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

    // ========================
    // PASSWORD MANAGEMENT
    // ========================

    // FUNCTION: สร้างรหัสผ่านแบบสุ่ม
    generateRandomPassword() {
        const letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const numbers = '0123456789';
        
        let password = '';
        for (let i = 0; i < 4; i++) {
            password += letters.charAt(Math.floor(Math.random() * letters.length));
        }
        for (let i = 0; i < 4; i++) {
            password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        }
        
        const passwordField = document.getElementById('staffPassword');
        const confirmField = document.getElementById('staffPasswordConfirm');
        if (passwordField) passwordField.value = password;
        if (confirmField) confirmField.value = password;
        
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
        this.showNotification('สร้างรหัสผ่านให้แล้ว: ' + password, 'success');
    }

    // FUNCTION: สลับการมองเห็นรหัสผ่าน
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

    // FUNCTION: คัดลอกรหัสผ่านไปยังคลิปบอร์ด
    async copyPassword() {
        const passwordText = document.getElementById('generatedPasswordText')?.textContent;
        if (!passwordText) return;

        try {
            await navigator.clipboard.writeText(passwordText);
            this.showNotification('คัดลอกรหัสผ่านแล้ว', 'success');
        } catch (err) {
            const textArea = document.createElement('textarea');
            textArea.value = passwordText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showNotification('คัดลอกรหัสผ่านแล้ว', 'success');
        }
    }

    // FUNCTION: อัปเดตระดับความแข็งแรงของรหัสผ่าน
    updatePasswordStrength(password) {
        const strengthDiv = document.getElementById('passwordStrength');
        if (!strengthDiv || !password) return;

        let strength = 0;
        let feedback = '';

        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
            case 2:
                strengthDiv.className = 'password-strength weak';
                feedback = 'รหัสผ่านอ่อน';
                break;
            case 3:
                strengthDiv.className = 'password-strength medium';
                feedback = 'รหัสผ่านปานกลาง';
                break;
            case 4:
            case 5:
                strengthDiv.className = 'password-strength strong';
                feedback = 'รหัสผ่านแข็งแรง';
                break;
        }

        strengthDiv.textContent = feedback;
        strengthDiv.style.display = 'block';
    }

    // ========================
    // FILTERING & SEARCHING
    // ========================

    // FUNCTION: ใช้ตัวกรองการค้นหา
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

    // FUNCTION: รีเซ็ตตัวกรองทั้งหมด
    resetFilters() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';

        const roleFilter = document.getElementById('roleFilter');
        if (roleFilter) roleFilter.value = '';

        const departmentFilter = document.getElementById('departmentFilter');
        if (departmentFilter) departmentFilter.value = '';

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) statusFilter.value = '';

        const sortFilter = document.getElementById('sortFilter');
        if (sortFilter) sortFilter.value = 'name';

        this.filteredStaff = [...this.staff];
        this.applySorting();
        this.currentPage = 1;
        this.renderStaff();
        
        this.showNotification('รีเซ็ตตัวกรองแล้ว', 'info');
    }

    // FUNCTION: ใช้การเรียงลำดับ
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

    // FUNCTION: จัดการการเปลี่ยนแปลงการเรียงลำดับ
    onSortChange() {
        this.applySorting();
        this.renderStaff();
    }

    // ========================
    // MODAL MANAGEMENT
    // ========================

    // FUNCTION: เปิดโมดัลเพิ่มพนักงานใหม่
    openAddModal() {
        const elements = {
            modalTitle: document.getElementById('modalTitle'),
            staffForm: document.getElementById('staffForm'),
            staffActive: document.getElementById('staffActive'),
            passwordGroup: document.getElementById('passwordGroup'),
            passwordConfirmGroup: document.getElementById('passwordConfirmGroup'),
            generatedPasswordDisplay: document.getElementById('generatedPasswordDisplay'),
            passwordStrength: document.getElementById('passwordStrength'),
            staffCode: document.getElementById('staffCode'),
            staffPassword: document.getElementById('staffPassword'),
            staffPasswordConfirm: document.getElementById('staffPasswordConfirm'),
            staffModal: document.getElementById('staffModal')
        };
        
        if (elements.modalTitle) elements.modalTitle.textContent = 'เพิ่มพนักงานใหม่';
        if (elements.staffForm) elements.staffForm.reset();
        if (elements.staffActive) elements.staffActive.checked = true;
        
        this.currentEditId = null;
        
        if (elements.passwordGroup) elements.passwordGroup.style.display = 'block';
        if (elements.passwordConfirmGroup) elements.passwordConfirmGroup.style.display = 'block';
        if (elements.staffPassword) elements.staffPassword.required = true;
        if (elements.staffPasswordConfirm) elements.staffPasswordConfirm.required = true;
        
        if (elements.generatedPasswordDisplay) elements.generatedPasswordDisplay.style.display = 'none';
        if (elements.passwordStrength) elements.passwordStrength.style.display = 'none';
        
        if (elements.staffCode) elements.staffCode.value = '';
        this.generateStaffCode();
        
        this.updateDepartmentAndPermissions();
        
        if (elements.staffModal) elements.staffModal.style.display = 'block';
    }

    // FUNCTION: ปิดโมดัลแบบฟอร์ม
    closeModal() {
        const staffModal = document.getElementById('staffModal');
        if (staffModal) staffModal.style.display = 'none';
        this.currentEditId = null;
    }

    // FUNCTION: ปิดโมดัลรายละเอียดพนักงาน
    closeStaffDetailsModal() {
        const staffDetailsModal = document.getElementById('staffDetailsModal');
        if (staffDetailsModal) staffDetailsModal.style.display = 'none';
    }

    // ========================
    // STAFF OPERATIONS
    // ========================

    // FUNCTION: ดูรายละเอียดพนักงาน
    viewStaffDetails(adminId) {
        const person = this.staff.find(s => s.admin_id === adminId);
        if (!person) return;

        const roleInfo = this.getRoleInfo(person.position);
        const statusInfo = this.getStatusInfo(person.status);
        const departmentName = this.departmentMap[person.department] || person.department;

        const elements = {
            title: document.getElementById('staffDetailsTitle'),
            name: document.getElementById('detailName'),
            phone: document.getElementById('detailPhone'),
            code: document.getElementById('detailCode'),
            role: document.getElementById('detailRole'),
            department: document.getElementById('detailDepartment'),
            status: document.getElementById('detailStatus')
        };

        if (elements.title) elements.title.textContent = `รายละเอียดพนักงาน - ${person.fullname}`;
        if (elements.name) elements.name.textContent = person.fullname;
        if (elements.phone) elements.phone.textContent = person.phone || 'ไม่ระบุ';
        if (elements.code) elements.code.textContent = person.admin_id || 'ไม่ระบุ';
        if (elements.role) elements.role.innerHTML = `<span class="role-badge ${roleInfo.class}">${roleInfo.text}</span>`;
        if (elements.department) elements.department.textContent = departmentName;
        if (elements.status) elements.status.innerHTML = `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;

        const staffDetailsModal = document.getElementById('staffDetailsModal');
        if (staffDetailsModal) staffDetailsModal.style.display = 'block';
    }

    // FUNCTION: แก้ไขข้อมูลพนักงาน
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
        
        if (elements.passwordGroup) elements.passwordGroup.style.display = 'none';
        if (elements.passwordConfirmGroup) elements.passwordConfirmGroup.style.display = 'none';
        if (elements.staffPassword) elements.staffPassword.required = false;
        if (elements.staffPasswordConfirm) elements.staffPasswordConfirm.required = false;

        this.updateDepartmentAndPermissions();

        const staffModal = document.getElementById('staffModal');
        if (staffModal) staffModal.style.display = 'block';
    }

    // FUNCTION: สลับสถานะพนักงาน (ใช้งาน/ไม่ใช้งาน) พร้อมยืนยัน
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

    // FUNCTION: ลบพนักงาน พร้อมยืนยัน
    async confirmDelete(adminId) {
        const person = this.staff.find(s => s.admin_id === adminId);
        if (!person) return;
        
        if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ "${person.fullname}"?\n\nการกระทำนี้ไม่สามารถถอนกลับได้`)) {
            const result = await this.deleteStaff(adminId);
            if (result.success) {
                this.showNotification(result.message, 'success');
            } else {
                this.showNotification(result.message, 'error');
            }
        }
    }

    // ========================
    // UTILITY METHODS
    // ========================

    // FUNCTION: สร้างรหัสพนักงาน
    generateStaffCode() {
        const staffCodeInput = document.getElementById('staffCode');
        if (!staffCodeInput) return;

        const randomNumber = Math.floor(1000 + Math.random() * 9000);
        let newCode = `EMP${randomNumber}`;
        const existingCodes = this.staff.map(s => s.admin_id);

        let attempts = 0;
        while (existingCodes.includes(newCode) && attempts < 100) {
            const newRandomNumber = Math.floor(1000 + Math.random() * 9000);
            newCode = `EMP${newRandomNumber}`;
            attempts++;
        }

        staffCodeInput.value = newCode;
    }

    // FUNCTION: ดึงข้อมูลสถานะ
    getStatusInfo(status) {
        const statusMap = {
            'active': { class: 'status-active', text: 'ใช้งานอยู่' },
            'inactive': { class: 'status-inactive', text: 'ไม่ได้ใช้งาน' }
        };
        return statusMap[status] || { class: 'status-inactive', text: 'ไม่ทราบ' };
    }

    // FUNCTION: ดึงข้อมูลบทบาท
    getRoleInfo(position) {
        return this.roleMap[position] || { class: 'role-other', text: position, avatar: '#666' };
    }

    // FUNCTION: ดึงอักษรตัวแรกของชื่อ
    getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    }

    // FUNCTION: อัปเดตแผนกและสิทธิ์
    updateDepartmentAndPermissions() {
        // ฟังก์ชันสำหรับ Event Listeners
    }

    // ========================
    // RENDERING
    // ========================

    // FUNCTION: แสดงตารางพนักงาน
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

    // FUNCTION: แสดงเลขหน้า
    renderPagination() {
        const totalPages = Math.ceil(this.filteredStaff.length / this.itemsPerPage);
        const pagination = document.getElementById('pagination');
        
        if (!pagination || totalPages <= 1) {
            if (pagination) pagination.innerHTML = '';
            return;
        }

        let buttons = [];
        
        buttons.push(`
            <button onclick="adminManager.changePage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `);

        for (let i = 1; i <= totalPages; i++) {
            buttons.push(`
                <button onclick="adminManager.changePage(${i})" ${i === this.currentPage ? 'class="active"' : ''}>
                    ${i}
                </button>
            `);
        }

        buttons.push(`
            <button onclick="adminManager.changePage(${this.currentPage + 1})" ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `);

        pagination.innerHTML = buttons.join('');
    }

    // FUNCTION: เปลี่ยนหน้า
    changePage(page) {
        const totalPages = Math.ceil(this.filteredStaff.length / this.itemsPerPage);
        if (page >= 1 && page <= totalPages) {
            this.currentPage = page;
            this.renderStaff();
        }
    }

    // FUNCTION: อัปเดตสถิติ
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

    // ========================
    // NOTIFICATIONS
    // ========================

    // FUNCTION: แสดงการแจ้งเตือน
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
        
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
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
}

// ========================
// GLOBAL FUNCTIONS
// ========================

let adminManager;

document.addEventListener('DOMContentLoaded', function() {
    adminManager = new AdminManager();
});

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
    const sidebar = document.getElementById("sidebar");
    const main = document.querySelector(".main-content");

    if (sidebar) {
        sidebar.classList.toggle("show");
        if (main) {
            main.classList.toggle("overlay");
        }
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminManager;
}