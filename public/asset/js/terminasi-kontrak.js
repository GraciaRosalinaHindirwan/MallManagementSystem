document.addEventListener("DOMContentLoaded", function () {
    const defaultContracts = [
        {
            id: 1,
            contractNo: "CTR-2025-0018",
            tenantName: "Kopi Nusantara",
            unitCode: "GF-018",
            endDate: "2026-07-14",
            outstanding: 0,
            status: "Active",
            unitStatus: "Occupied"
        },
        {
            id: 2,
            contractNo: "CTR-2024-0092",
            tenantName: "Urban Sneakers",
            unitCode: "L1-112",
            endDate: "2026-08-31",
            outstanding: 12500000,
            status: "Active",
            unitStatus: "Occupied"
        },
        {
            id: 3,
            contractNo: "CTR-2025-0037",
            tenantName: "Glow Beauty Studio",
            unitCode: "L2-207",
            endDate: "2026-09-09",
            outstanding: 0,
            status: "Active",
            unitStatus: "Occupied"
        },
        {
            id: 4,
            contractNo: "CTR-2023-0064",
            tenantName: "Pixel Gadget",
            unitCode: "L3-305",
            endDate: "2026-05-31",
            outstanding: 0,
            status: "Terminated",
            unitStatus: "Available",
            terminationDate: "2026-05-31",
            terminationType: "contract_end",
            terminationReason: "Kontrak berakhir dan tenant tidak melakukan perpanjangan."
        }
    ];

    let contracts = JSON.parse(localStorage.getItem("gabrielContracts")) || defaultContracts;

    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const contractTable = document.getElementById("contractTable");
    const emptyMessage = document.getElementById("emptyMessage");
    const alertBox = document.getElementById("alertBox");

    const terminationSection = document.getElementById("terminationSection");
    const detailSection = document.getElementById("detailSection");
    const contractSelect = document.getElementById("contractId");
    const terminationDate = document.getElementById("terminationDate");
    const cancelButton = document.getElementById("cancelButton");
    const closeDetailButton = document.getElementById("closeDetailButton");
    const terminationForm = document.getElementById("terminationForm");

    const typeLabels = {
        contract_end: "Kontrak Berakhir",
        early_termination: "Terminasi Lebih Awal",
        breach: "Pelanggaran Kontrak",
        mutual_agreement: "Kesepakatan Bersama"
    };

    function saveContracts() {
        localStorage.setItem("gabrielContracts", JSON.stringify(contracts));
    }

    function rupiah(value) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(value);
    }

    function showAlert(message, type) {
        alertBox.hidden = false;
        alertBox.className = "alert " + type;
        alertBox.textContent = message;

        setTimeout(function () {
            alertBox.hidden = true;
        }, 4000);
    }

    function populateContractSelect() {
        contractSelect.innerHTML = '<option value="">Pilih kontrak</option>';

        contracts
            .filter(function (contract) {
                return contract.status === "Active";
            })
            .forEach(function (contract) {
                const option = document.createElement("option");
                option.value = contract.id;
                option.textContent =
                    contract.contractNo + " - " + contract.tenantName + " - " + contract.unitCode;
                contractSelect.appendChild(option);
            });
    }

    function renderTable() {
        const keyword = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value;

        const filteredContracts = contracts.filter(function (contract) {
            const searchableText =
                contract.contractNo.toLowerCase() +
                " " +
                contract.tenantName.toLowerCase() +
                " " +
                contract.unitCode.toLowerCase();

            const matchSearch = searchableText.includes(keyword);
            const matchStatus =
                selectedStatus === "all" || contract.status === selectedStatus;

            return matchSearch && matchStatus;
        });

        contractTable.innerHTML = "";

        filteredContracts.forEach(function (contract) {
            const row = document.createElement("tr");

            const outstandingText =
                contract.outstanding > 0
                    ? '<span class="text-warning">' + rupiah(contract.outstanding) + "</span>"
                    : '<span class="text-success">Lunas</span>';

            const actionButton =
                contract.status === "Active"
                    ? '<button type="button" class="btn btn-danger" data-open-form="' +
                      contract.id +
                      '">Terminasi</button>'
                    : '<button type="button" class="btn btn-secondary" data-view-detail="' +
                      contract.id +
                      '">Detail</button>';

            row.innerHTML =
                "<td>" + contract.contractNo + "</td>" +
                "<td>" + contract.tenantName + "</td>" +
                "<td>" + contract.unitCode + "</td>" +
                "<td>" + contract.endDate + "</td>" +
                "<td>" + outstandingText + "</td>" +
                '<td><span class="status ' + contract.status.toLowerCase() + '">' +
                contract.status +
                "</span></td>" +
                "<td>" + contract.unitStatus + "</td>" +
                "<td>" + actionButton + "</td>";

            contractTable.appendChild(row);
        });

        emptyMessage.hidden = filteredContracts.length !== 0;
    }

    searchInput.addEventListener("input", renderTable);
    statusFilter.addEventListener("change", renderTable);

    contractTable.addEventListener("click", function (event) {
        const terminationButton = event.target.closest("[data-open-form]");
        const detailButton = event.target.closest("[data-view-detail]");

        if (terminationButton) {
            contractSelect.value = terminationButton.dataset.openForm;
            terminationDate.value = new Date().toISOString().slice(0, 10);

            detailSection.hidden = true;
            terminationSection.hidden = false;
            terminationSection.scrollIntoView({ behavior: "smooth" });
        }

        if (detailButton) {
            const contract = contracts.find(function (item) {
                return item.id === Number(detailButton.dataset.viewDetail);
            });

            if (!contract) return;

            document.getElementById("detailTenant").textContent = contract.tenantName;
            document.getElementById("detailContract").textContent = contract.contractNo;
            document.getElementById("detailUnit").textContent = contract.unitCode;
            document.getElementById("detailDate").textContent = contract.terminationDate || "-";
            document.getElementById("detailType").textContent =
                typeLabels[contract.terminationType] || "-";
            document.getElementById("detailReason").textContent =
                contract.terminationReason || "-";

            terminationSection.hidden = true;
            detailSection.hidden = false;
            detailSection.scrollIntoView({ behavior: "smooth" });
        }
    });

    cancelButton.addEventListener("click", function () {
        terminationSection.hidden = true;
        terminationForm.reset();
    });

    closeDetailButton.addEventListener("click", function () {
        detailSection.hidden = true;
    });

    terminationForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const contractId = Number(contractSelect.value);
        const selectedContract = contracts.find(function (contract) {
            return contract.id === contractId;
        });

        const checklistItems = Array.from(
            document.querySelectorAll(".termination-checklist")
        );
        const allChecked = checklistItems.every(function (item) {
            return item.checked;
        });

        if (!selectedContract) {
            showAlert("Pilih kontrak tenant terlebih dahulu.", "error");
            return;
        }

        if (!allChecked) {
            showAlert("Semua checklist penyelesaian wajib dicentang.", "error");
            return;
        }

        const confirmed = confirm(
            "Yakin ingin memproses terminasi? Status unit akan berubah menjadi Available."
        );

        if (!confirmed) {
            return;
        }

        selectedContract.status = "Terminated";
        selectedContract.unitStatus = "Available";
        selectedContract.terminationDate = terminationDate.value;
        selectedContract.terminationType =
            document.getElementById("terminationType").value;
        selectedContract.terminationReason =
            document.getElementById("reason").value.trim();

        saveContracts();
        populateContractSelect();
        renderTable();

        terminationForm.reset();
        terminationSection.hidden = true;

        showAlert(
            "Terminasi berhasil. Status kontrak menjadi Terminated dan unit menjadi Available.",
            "success"
        );
    });

    populateContractSelect();
    renderTable();
});
