    document.getElementById("update_attraction").addEventListener("submit", async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append("action", "update_attraction");

        try {
            const response = await fetch("api.php", {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                updateUI(result, form);
                form.reset();
            } else {
                alert("เกิดข้อผิดพลาด: " + result.message);
            }
        } catch (error) {
            alert("❌ ไม่สามารถเชื่อมต่อ API ได้");
            console.error(error);
        }
    });

    document.getElementById("add_attraction").addEventListener("submit", async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append("action", "add_attraction");

        try {
            const response = await fetch("api.php", {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                updateUI(result, form, true); // 🔹 เพิ่ม third param = true แสดงว่าเป็น add ใหม่
                form.reset();
            } else {
                alert("เกิดข้อผิดพลาด: " + result.message);
            }
        } catch (error) {
            alert("❌ ไม่สามารถเชื่อมต่อ API ได้");
            console.error(error);
        }
    });

    function updateUI(result, form, isNew = false) {
        if (!result.success) return;

        const cardContainer = document.getElementById("attractionList");

        const id = result.id || form.querySelector("[name='id']")?.value;
        const name = form.querySelector("[name='name']").value;
        const description = form.querySelector("[name='description']").value;
        const address = form.querySelector("[name='address']").value;
        const phone = form.querySelector("[name='phone']").value;
        const rating = form.querySelector("[name='rating']").value;
        const fee = form.querySelector("[name='entrance_fee']").value;
        const open = form.querySelector("[name='opening_time']").value;
        const close = form.querySelector("[name='close_time']").value;

        if (!isNew && id) {
            // 🔹 อัปเดต card เก่า
            const card = document.querySelector(`[data-id="${id}"]`)?.closest(".card");
            if (card) {
                card.querySelector(".fw-semibold").textContent = name;
                card.querySelector(".card-body").innerHTML = `
                <p><strong>คำอธิบาย:</strong> ${description}</p>
                <p><strong>ที่อยู่:</strong> ${address}</p>
                <p><strong>โทร:</strong> ${phone}</p>
                <p><strong>เวลาเปิด:</strong> ${open} - ${close}</p>
                <p><strong>ค่าเข้า:</strong> ${fee} บาท</p>
                <p><strong>Rating:</strong> ${rating}</p>
            `;
            }
            bootstrap.Modal.getInstance(document.getElementById("editModal"))?.hide();
        } else if (isNew) {
            // 🔹 เพิ่ม card ใหม่
            const newCard = document.createElement("div");
            newCard.className = "col-lg-4 col-md-6 col-sm-12 mb-4";
            newCard.innerHTML = `
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">${name}</span>
                    <button class="btn btn-light btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#editModal"
                        data-id="${result.id}"
                        data-name="${name}"
                        data-description="${description}"
                        data-address="${address}"
                        data-phone="${phone}"
                        data-rating="${rating}"
                        data-opening="${open}"
                        data-close="${close}"
                        data-fee="${fee}">
                        <i class="fa fa-edit"></i>
                    </button>
                </div>
                <div class="card-body">
                    <p><strong>คำอธิบาย:</strong> ${description}</p>
                    <p><strong>ที่อยู่:</strong> ${address}</p>
                    <p><strong>โทร:</strong> ${phone}</p>
                    <p><strong>เวลาเปิด:</strong> ${open} - ${close}</p>
                    <p><strong>ค่าเข้า:</strong> ${fee} บาท</p>
                    <p><strong>Rating:</strong> ${rating}</p>
                </div>
            </div>
        `;
            cardContainer.prepend(newCard);
        }
    }



    const form = document.getElementById("search-form");
    const result = document.getElementById("result");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const id = formData.get("id").trim();
        if (!id) {
            result.innerHTML = "ไม่ได้ใส่ ไอดี";
            return;
        }

        try {
            const response = await fetch(`api.php?id=${encodeURIComponent(id)}`);
            const data = await response.json();
            console.log(data);
            if (data.success) {
                result.innerHTML = `
                <p><strong>ID:</strong> ${data.data.id}</p>
                <p><strong>ชื่อ:</strong> ${data.data.name}</p>
                <p><strong>คำอธิบาย:</strong> ${data.data.description}</p>
            `;
            } else {
                result.innerHTML = data.message;
            }

        } catch (error) {
            result.innerHTML = "❌ เกิดข้อผิดพลาด";
            console.error(error);
        }
    });