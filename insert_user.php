<?php 
header('Content-Type: application/json');   

$servername = "localhost";
$user = "root";
$password = "";
$db_name = "quanlydoanvien";

$conn = new mysqli($servername, $user, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Lỗi kết nối với server"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$required_fields = ['ten', 'id', 'password', 'email', 'phone', 'gender', 'birthdate', 'userClass', 'department', 'role', 'yearin'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        echo json_encode(["success" => false, "message" => "Thiếu dữ liệu trường: $field"]);
        exit;
    }
}

$name = trim($data['ten']);
$id = $data['id'];
$pass = $data['password'];
$email = trim($data['email']);
$phone = trim($data['phone']);
$gender = $data['gender'] === 'nam' ? 'Nam' : 'Nữ'; // Convert to proper case
$birth = $data['birthdate'];
$userClass = $data['userClass']; 
$department = $data['department'];
$chidoan = $department[0]; 
$role = $data['role']; 
$yearin = $data['yearin'];
$id_admin = $data['insert_admin'];

$hash_pass = password_hash($pass, PASSWORD_DEFAULT);

if (available($conn, $id)) {
    echo json_encode(["success" => false, "message" => "Mã sinh viên đã tồn tại"]);
    exit;
}

$username = account_name($name, $id);

$stmt = $conn->prepare("INSERT INTO doanvien (id, ho_ten, gioi_tinh, ngay_sinh, lop_id, chidoan_id, khoa, email, sdt, chuc_vu, nienkhoa, password)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssiissssis", $id, $name, $gender, $birth, $userClass, $chidoan, $department, $email, $phone, $role, $yearin, $hash_pass);

if ($stmt->execute()) {
    // Insert notification
    $noidung = "thêm vào đoàn viên " . $id;
    $stmt_notify = $conn->prepare("INSERT INTO thongbao (id_actor, loai, noidung, id_affected) VALUES (?, 'insert', ?, ?)");
    $stmt_notify->bind_param("isi", $id_admin, $noidung, $id);
    $stmt_notify->execute();
    $stmt_notify->close();

    echo json_encode(["success" => true, "message" => "Thêm tài khoản thành công"]);
} else {
    echo json_encode(["success" => false, "message" => "Lỗi khi thêm tài khoản: " . $stmt->error]);
}

$stmt->close();
$conn->close();

function account_name($name, $id)
{
    $name_arr = explode(" ", $name);
    $last_name = end($name_arr);
    return strtolower($last_name . $id);
}

function available($conn, $id)
{
    $sql = "SELECT id FROM doanvien WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
?>
