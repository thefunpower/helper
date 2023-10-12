<?php 
/**
* HTML端收消息
*/
function get_ws_js($func,$port = 3006){
	return "
	const socket = new WebSocket('ws://127.0.0.1:".$port."'); 
	socket.onopen = function() {
	  console.log('WebSocket connection is open');
	}; 
	socket.onmessage = function(event) { 
	  let data = event.data;
	  // 处理接收到的消息
	  ".$func."
	};

	socket.onclose = function(event) {
	  console.log('WebSocket connection is closed');
	};";
}
/**
* 生成node js ws服务
* 需要把返回的内容写入server.js中
* 依赖 ioredis 
*/
function create_node_ws_server($ws_port=3006,$topic=['demo'],$redis_host='127.0.0.1',$port='6379',$auth=''){
	$str1 = '';
	$str2 = '';
	foreach($topic as $v){
		$str1 .= "redisClient.subscribe('".$v."');";
		$str2 .= "redisClient.publish('".$v."', message);"; 
	}
	return "
	const WebSocket = require('ws');
	const Redis = require('ioredis'); 
	const wss = new WebSocket.Server({ port: ".$ws_port." });
	const redisClient = new Redis({
	  host: '".$redis_host."',
	  port: '".$port."',
	  password: '".$auth."',
	});
	// 存储所有的WebSocket连接
	const clients = new Set();
	wss.on('connection', function connection(ws) {
	  console.log('A client connected');	  
	  // 添加新的WebSocket连接到集合中
	  clients.add(ws);
	  ws.on('message', function incoming(message) {
	    console.log('Received message:', message);
	    // 在这里处理接收到的WebSocket消息
	    // 将接收到的WebSocket消息发布到Redis频道
	    ".$str2."
	  });
	  ws.on('close', function() {
	    console.log('A client disconnected');
	    // 移除已关闭的WebSocket连接
	    clients.delete(ws);
	  });
	});

	// 监听Redis频道消息
	".$str1."
	redisClient.on('message', function(channel, message) {
	  console.log('Redis message:', message);
	  // 将Redis频道消息广播给所有WebSocket连接的客户端
	  clients.forEach(function(client) {
	    if (client.readyState === WebSocket.OPEN) {
	      client.send(message);
	    }
	  });
	});
	console.log('WebSocket and Redis server is running on port ".$ws_port."');
	";
}