<?php
// --- ส่วนการเชื่อมต่อฐานข้อมูล (Config) ---
$host = "localhost"; // หรือ IP ของ Container MariaDB ใน Dokploy
$user = "root";      // ตามที่คุณตั้งค่าใน Dokploy
$pass = "your_password"; 
$dbname = "bst_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- การจัดการเมื่อมีการ Insert Node (POST) ---
if (isset($_POST['add_node'])) {
    $val = intval($_POST['nodeValue']);
    // ตรวจสอบข้อมูลซ้ำเบื้องต้น (Optional)
    $stmt = $conn->prepare("INSERT INTO tree_nodes (node_value) VALUES (?)");
    $stmt->bind_param("i", $val);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']); // Refresh เพื่อแสดงผล
}

// --- การจัดการเมื่อกด Reset (ล้างตาราง) ---
if (isset($_POST['reset_tree'])) {
    $conn->query("TRUNCATE TABLE tree_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
}

// --- ดึงข้อมูลจาก Database มาเก็บใน Array ของ PHP ---
$nodes_from_db = [];
$result = $conn->query("SELECT node_value FROM tree_nodes ORDER BY id ASC");
while($row = $result->fetch_assoc()) {
    $nodes_from_db[] = $row['node_value'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Cyberpunk BST with MariaDB</title>
    <style>
        /* CSS เดิมของคุณ (ตัดมาบางส่วนเพื่อความกระชับ) */
        body { background-color: #0b0c10; color: #c5c6c7; font-family: 'Courier New', monospace; text-align: center; }
        .neon-text { color: #66fcf1; text-shadow: 0 0 10px #66fcf1; }
        .controls { margin-bottom: 20px; }
        input[type="number"] { background: #1f2833; border: 2px solid #45a29e; color: #fff; padding: 10px; border-radius: 5px; }
        .neon-btn { background: transparent; color: #66fcf1; border: 2px solid #66fcf1; padding: 10px 20px; cursor: pointer; font-weight: bold; }
        .neon-btn:hover { background: #66fcf1; color: #000; box-shadow: 0 0 15px #66fcf1; }
        #tree-canvas { position: relative; width: 100%; height: 600px; }
        #wires { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
        .wire-path { fill: none; stroke: #ff0055; stroke-width: 3; filter: drop-shadow(0 0 5px #ff0055); stroke-dasharray: 10; animation: flow 1s linear infinite; }
        @keyframes flow { to { stroke-dashoffset: -20; } }
        .tree-node { position: absolute; width: 50px; height: 50px; background-color: #1f2833; border: 3px solid #66fcf1; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: #fff; z-index: 2; transform: translate(-50%, -50%); box-shadow: 0 0 15px #66fcf1; }
    </style>
</head>
<body>

    <h1 class="neon-text">DB DATA ROUTER (BST)</h1>
    
    <div class="controls">
        <form method="POST" style="display: inline;">
            <input type="number" name="nodeValue" placeholder="ป้อนตัวเลข..." required>
            <button type="submit" name="add_node" class="neon-btn">+ INSERT TO DB</button>
            <button type="submit" name="reset_tree" class="neon-btn" style="color:#ff0055; border-color:#ff0055;">RESET DB</button>
        </form>
    </div>

    <div id="tree-canvas">
        <svg id="wires"></svg>
    </div>

    <script>
        // --- การดึงข้อมูลจาก PHP Array เข้าสู่ JavaScript ---
        const dbNodes = <?php echo json_encode($nodes_from_db); ?>;

        class Node {
            constructor(value) {
                this.value = value;
                this.left = null;
                this.right = null;
            }
        }

        let root = null;

        // ฟังก์ชันสร้าง Tree จากข้อมูลใน DB
        function buildTreeFromDB() {
            dbNodes.forEach(val => {
                if (root === null) root = new Node(val);
                else insertIntoBST(root, val);
            });
            renderTree();
        }

        function insertIntoBST(node, val) {
            if (val < node.value) {
                if (node.left === null) node.left = new Node(val);
                else insertIntoBST(node.left, val);
            } else if (val > node.value) {
                if (node.right === null) node.right = new Node(val);
                else insertIntoBST(node.right, val);
            }
        }

        // Logic การวาด (คงเดิมจากโค้ดที่คุณให้มา)
        function renderTree() {
            const canvas = document.getElementById('tree-canvas');
            const svgWires = document.getElementById('wires');
            svgWires.innerHTML = '';
            document.querySelectorAll('.tree-node').forEach(el => el.remove());

            if (root !== null) {
                const startX = canvas.clientWidth / 2;
                const startY = 40;
                const initialOffset = canvas.clientWidth / 4;
                traverseAndDraw(root, startX, startY, initialOffset);
            }
        }

        function traverseAndDraw(node, x, y, offset) {
            const canvas = document.getElementById('tree-canvas');
            const svgWires = document.getElementById('wires');

            if (node.left !== null) {
                const childX = x - offset;
                const childY = y + 80;
                drawWire(svgWires, x, y, childX, childY);
                traverseAndDraw(node.left, childX, childY, offset / 1.8);
            }
            if (node.right !== null) {
                const childX = x + offset;
                const childY = y + 80;
                drawWire(svgWires, x, y, childX, childY);
                traverseAndDraw(node.right, childX, childY, offset / 1.8);
            }

            const nodeDiv = document.createElement('div');
            nodeDiv.className = 'tree-node';
            nodeDiv.innerText = node.value;
            nodeDiv.style.left = x + 'px';
            nodeDiv.style.top = y + 'px';
            canvas.appendChild(nodeDiv);
        }

        function drawWire(svgElement, startX, startY, endX, endY) {
            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            const d = `M ${startX} ${startY} C ${startX} ${startY + 40}, ${endX} ${endY - 40}, ${endX} ${endY}`;
            path.setAttribute("d", d);
            path.setAttribute("class", "wire-path");
            svgElement.appendChild(path);
        }

        // เริ่มต้นวาดเมื่อโหลดหน้าจอ
        window.onload = buildTreeFromDB;
        window.onresize = renderTree;
    </script>
</body>
</html>
