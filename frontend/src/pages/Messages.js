// import React, { useEffect, useRef, useState } from "react";
// import API from "../api";
// import "../styles/message.css";
// import Echo from "laravel-echo";
// import Pusher from "pusher-js";

// window.Pusher = Pusher;

// const echo = new Echo({
//   broadcaster: "reverb",
//   key: "2uzrl6iaj0k9iedjfuwx", // 👈 match REVERB_APP_KEY in .env
//   wsHost: "127.0.0.1",
//   wsPort: 8080,
//   forceTLS: false,
//   enabledTransports: ["ws"],
// });

// export default function Messages() {
//   const [messages, setMessages] = useState([]);
//   const [input, setInput] = useState("");
//   const [loading, setLoading] = useState(false);
//   const [error, setError] = useState(null);
//   const bottomRef = useRef(null);

//   /** 📨 Load all messages once */
//   const loadMessages = async () => {
//     try {
//       setLoading(true);
//       const res = await API.get("/messages");
//       setMessages(res.data);
//       setError(null);
//     } catch (err) {
//       console.error(err);
//       setError("Failed to load messages");
//     } finally {
//       setLoading(false);
//     }
//   };

//   /** 🔊 Listen for realtime broadcasts */
//   useEffect(() => {
//     loadMessages();

//     const channel = echo.channel("chat");
//     channel.listen("MessageSent", (event) => {
//       console.log("🔔 New message received:", event.message);
//       setMessages((prev) => [...prev, event.message]);
//     });

//     return () => {
//       echo.leaveChannel("chat");
//     };
//   }, []);

//   /** ⬇️ Auto-scroll to bottom when messages update */
//   useEffect(() => {
//     bottomRef.current?.scrollIntoView({ behavior: "smooth" });
//   }, [messages]);

//   /** 🚀 Handle sending new message */
//   const handleSend = async () => {
//     if (!input.trim()) return;

//     // optimistic UI
//     const optimistic = {
//       id: `temp-${Date.now()}`,
//       text: input,
//       created_at: new Date().toISOString(),
//       user: { name: "You" },
//     };
//     setMessages((prev) => [...prev, optimistic]);
//     setInput("");

//     try {
//       const res = await API.post("/messages", { text: input });
//       // replace optimistic message with the server message
//       setMessages((prev) =>
//         prev.filter((m) => !String(m.id).startsWith("temp-")).concat(res.data)
//       );
//     } catch (err) {
//       console.error(err);
//       setError("Failed to send message");
//       loadMessages();
//     }
//   };

//   return (
//     <div className="message-page">
//       {/* 🧭 Left Section */}
//       <div className="left-section">
//         <h2>New Messages</h2>
//         <ul>
//           <li>John Doe</li>
//           <li>Jane Smith</li>
//           <li>Support Team</li>
//         </ul>
//       </div>

//       {/* 💬 Chat Section */}
//       <div className="center-section">
//         <div className="chat-box">
//           {loading ? (
//             <p>Loading messages…</p>
//           ) : messages.length === 0 ? (
//             <p className="no-messages">No messages yet...</p>
//           ) : (
//             messages.map((msg) => (
//               <div key={msg.id} className="message">
//                 <div className="message-meta">
//                   <small>
//                     {msg.user?.name ?? "Anonymous"} •{" "}
//                     {new Date(msg.created_at).toLocaleTimeString()}
//                   </small>
//                 </div>
//                 <div className="message-text">{msg.text}</div>
//               </div>
//             ))
//           )}
//           <div ref={bottomRef} />
//         </div>

//         <textarea
//           className="message-input"
//           placeholder="Type your message..."
//           value={input}
//           onChange={(e) => setInput(e.target.value)}
//           onKeyDown={(e) => e.key === "Enter" && !e.shiftKey && handleSend()}
//         />

//         {error && <div style={{ color: "red", marginTop: 8 }}>{error}</div>}
//       </div>

//       {/* ➤ Send Button */}
//       <div className="right-section">
//         <button className="send-btn" onClick={handleSend}>
//           Send
//         </button>
//       </div>
//     </div>
//   );
// }

import React, { useEffect, useRef, useState } from "react";
import API from "../api";
import "../styles/message.css";
import echo from "../echo";

export default function Messages() {
  const [conversations, setConversations] = useState([
    { id: 1, name: "John Doe" },
    { id: 2, name: "Jane Smith" },
    { id: 3, name: "Support Team" },
  ]);

  const [activeChat, setActiveChat] = useState(null);
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const bottomRef = useRef(null);

  // Load messages for selected chat
  const loadMessages = async (chatId) => {
    try {
      setLoading(true);
      const res = await API.get(`/messages?chat_id=${chatId}`);
      setMessages(res.data);
      setError(null);
    } catch (err) {
      console.error(err);
      setError("Failed to load messages");
    } finally {
      setLoading(false);
    }
  };

  // Listen for real-time events
  useEffect(() => {
    const channel = echo.channel("chat");
    channel.listen("MessageSent", (event) => {
      setMessages((prev) => [...prev, event.message]);
    });

    return () => {
      echo.leaveChannel("chat");
    };
  }, []);

  // Scroll to bottom on new messages
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  // Send a message
  const handleSend = async () => {
    if (!input.trim() || !activeChat) return;
    const optimistic = {
      id: `temp-${Date.now()}`,
      text: input,
      created_at: new Date().toISOString(),
    };
    setMessages((prev) => [...prev, optimistic]);
    setInput("");

    try {
      const res = await API.post("/messages", { text: input, chat_id: activeChat.id });
      setMessages((prev) => [
        ...prev.filter((m) => !String(m.id).startsWith("temp-")),
        res.data.data,
      ]);
    } catch (err) {
      console.error(err);
      setError("Failed to send message");
      loadMessages(activeChat.id);
    }
  };

  return (
    <div className="message-page">
      {/* LEFT SIDEBAR */}
      <div className="left-section">
        <h2>Messages</h2>
        <ul>
          {conversations.map((conv) => (
            <li
              key={conv.id}
              onClick={() => {
                setActiveChat(conv);
                loadMessages(conv.id);
              }}
              className={activeChat?.id === conv.id ? "active" : ""}
            >
              {conv.name}
            </li>
          ))}
        </ul>
      </div>

      {/* CENTER CHAT AREA */}
      <div className="center-section">
        {activeChat ? (
          <>
            <div className="chat-header">
              <h3>{activeChat.name}</h3>
            </div>

            <div className="chat-box">
              {loading ? (
                <p>Loading messages…</p>
              ) : messages.length === 0 ? (
                <p className="no-messages">No messages yet...</p>
              ) : (
                messages.map((msg) => (
                  <div key={msg.id} className="message">
                    <div className="message-meta">
                      <small>
                        {msg.user?.name ?? "You"} •{" "}
                        {new Date(msg.created_at).toLocaleTimeString()}
                      </small>
                    </div>
                    <div className="message-text">{msg.text}</div>
                  </div>
                ))
              )}
              <div ref={bottomRef} />
            </div>

            <textarea
              className="message-input"
              placeholder="Type your message..."
              value={input}
              onChange={(e) => setInput(e.target.value)}
            ></textarea>

            <div className="send-section">
              <button className="send-btn" onClick={handleSend}>
                Send
              </button>
            </div>
          </>
        ) : (
          <p className="no-chat-selected">Select a conversation to start chatting</p>
        )}
      </div>
    </div>
  );
}

