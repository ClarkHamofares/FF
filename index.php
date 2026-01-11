<?php
session_start();

$conn = new mysqli("localhost","root","","pubg_charge");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$botToken = "8040046212:AAGlhEHjICyKJYww35tflD0QIVx_iktsmfQ";
$chat_id = "5058927918";

if(isset($_POST['register'])){
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, phone, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $phone, $password);
    $stmt->execute();
    $stmt->close();
    $msg = "تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن.";
}

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=?");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);

    if($stmt->num_rows > 0){
        $stmt->fetch();
        if(password_verify($password, $hashed_password)){
            $_SESSION['user_id'] = $id;
            $msg = "تم تسجيل الدخول بنجاح";
        } else { $msg = "كلمة المرور خاطئة"; }
    } else { $msg = "اسم المستخدم غير موجود"; }
    $stmt->close();
}

if(isset($_POST['order'])){
    if(!isset($_SESSION['user_id'])){ die("يجب تسجيل الدخول أولاً"); }

    $user_id = $_SESSION['user_id'];
    $pubg_id = $_POST['pubg_id'];
    $uc_amount = $_POST['uc_amount'];
    $phone_cash = $_POST['phone_cash'];

    if(!is_dir("uploads")){ mkdir("uploads"); }
    $screenshot_name = "uploads/".time()."_".$_FILES['screenshot']['name'];
    move_uploaded_file($_FILES['screenshot']['tmp_name'], $screenshot_name);

    $stmt = $conn->prepare("INSERT INTO orders (user_id, pubg_id, uc_amount, screenshot, phone_cash) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss",$user_id, $pubg_id, $uc_amount, $screenshot_name, $phone_cash);
    $stmt->execute();
    $stmt->close();

    $text = "📥 طلب شحن جديد
👤 PUBG ID: $pubg_id
💎 عدد الشدات: $uc_amount
📱 رقم فودافون كاش: $phone_cash
⏰ الوقت: ".date('Y-m-d H:i:s');

    $url = "https://api.telegram.org/bot$botToken/sendPhoto";
    $post = ['chat_id'=>$chat_id,'caption'=>$text,'photo'=>new CURLFile($screenshot_name)];
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_exec($ch);
    curl_close($ch);

    $msg = "تم إرسال الطلب بنجاح!";
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>موقع شحن شدات PUBG</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<h1>موقع شحن شدات PUBG</h1>

<?php if(isset($msg)) echo "<p>$msg</p>"; ?>

<h2>تسجيل حساب</h2>
<form method="POST">
<input type="text" name="username" placeholder="اسم المستخدم" required><br>
<input type="text" name="phone" placeholder="رقم الهاتف" required><br>
<input type="password" name="password" placeholder="كلمة المرور" required><br>
<button type="submit" name="register">إنشاء حساب</button>
</form>

<hr>

<h2>تسجيل دخول</h2>
<form method="POST">
<input type="text" name="username" placeholder="اسم المستخدم" required><br>
<input type="password" name="password" placeholder="كلمة المرور" required><br>
<button type="submit" name="login">تسجيل الدخول</button>
</form>

<hr>

<?php if(isset($_SESSION['user_id'])): ?>
<h2>طلب شحن شدات</h2>
<form method="POST" enctype="multipart/form-data">
<input type="text" name="pubg_id" placeholder="PUBG ID" required><br>
<input type="number" name="uc_amount" placeholder="عدد الشدات" required><br>
<input type="file" name="screenshot" required><br>
<input type="text" name="phone_cash" value="01015506479" required><br>
<button type="submit" name="order">إرسال الطلب</button>
</form>
<?php endif; ?>

</body>
</html>
