<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Demo - Real Contacts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-2xl font-bold mb-6">Chat Demo - Real Contacts from Database</h1>
        
        <!-- Real Contacts from Database -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Real Contacts ({{ $contacts->count() }} found)</h2>
            <div class="space-y-2">
                @foreach($contacts as $contact)
                    <div class="contact-item flex items-center p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors border" 
                         data-contact-id="{{ $contact->id }}" 
                         data-contact-name="{{ $contact->name }}">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                {{ substr($contact->name, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $contact->name }}</p>
                            <p class="text-sm text-gray-500">ID: {{ $contact->id }} | Business: {{ $contact->business_id }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Chat Interface -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Chat Interface</h2>
            
            <!-- Chat Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <div class="flex items-center">
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900" id="chatTitle">Select a contact to start chatting</h3>
                        <p class="text-sm text-gray-500" id="chatSubtitle"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button id="contactInfoBtn" class="p-2 text-gray-400 hover:text-gray-600 transition-colors" title="Contact Info" disabled>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    <button id="callBtn" class="p-2 text-gray-400 hover:text-gray-600 transition-colors" title="Call Contact" disabled>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Messages Area -->
            <div class="h-64 overflow-y-auto p-4" id="messagesContainer">
                <div class="text-center text-gray-500 mt-20">
                    <h3 class="text-sm font-medium text-gray-900">Select a contact to start chatting</h3>
                    <p class="mt-1 text-sm text-gray-500">Click on a contact above to begin.</p>
                </div>
            </div>
            
            <!-- Message Input -->
            <div class="p-4 border-t" id="messageInputContainer" style="display: none;">
                <div class="flex items-center space-x-2">
                    <input type="text" 
                           id="messageInput" 
                           placeholder="Type a message..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button id="sendMessageBtn" 
                            class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentContactId = null;
        
        // Debug: Check if buttons exist
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking buttons...');
            const contactInfoBtn = document.getElementById('contactInfoBtn');
            const callBtn = document.getElementById('callBtn');
            
            console.log('Contact Info button found:', contactInfoBtn);
            console.log('Call button found:', callBtn);
            
            if (contactInfoBtn) {
                console.log('Contact Info button is ready');
            } else {
                console.error('Contact Info button not found!');
            }
            
            if (callBtn) {
                console.log('Call button is ready');
            } else {
                console.error('Call button not found!');
            }
            
            // Add contact info functionality
            if (contactInfoBtn) {
                contactInfoBtn.addEventListener('click', function() {
                    console.log('Contact info button clicked!');
                    
                    if (!currentContactId) {
                        alert('Please select a contact first');
                        return;
                    }
                    
                    const contactName = document.getElementById('chatTitle').textContent.replace('Chat with ', '');
                    console.log('Contact name:', contactName);
                    
                    // Show contact info modal
                    const contactInfoModal = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="contactInfoModal">
                            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                            ${contactName.charAt(0)}
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900">${contactName}</h4>
                                            <p class="text-sm text-gray-500">Online</p>
                                        </div>
                                    </div>
                                    <div class="border-t pt-3">
                                        <p class="text-sm text-gray-600">Role: Staff Member</p>
                                        <p class="text-sm text-gray-600">Department: General</p>
                                        <p class="text-sm text-gray-600">Last seen: Just now</p>
                                    </div>
                                    <button onclick="closeContactInfoModal()" class="w-full bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.insertAdjacentHTML('beforeend', contactInfoModal);
                });
            }
            
            // Add call functionality (simplified for in-app messaging)
            if (callBtn) {
                callBtn.addEventListener('click', function() {
                    console.log('Call button clicked!');
                    
                    if (!currentContactId) {
                        alert('Please select a contact first');
                        return;
                    }
                    
                    const contactName = document.getElementById('chatTitle').textContent.replace('Chat with ', '');
                    console.log('Contact name for call:', contactName);
                    
                    // Show simple call options
                    const callOptions = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="callModal">
                            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Call ${contactName}</h3>
                                <div class="space-y-3">
                                    <button onclick="initiateCall('phone')" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                                        📞 Call Phone
                                    </button>
                                    <button onclick="initiateCall('message')" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                                        💬 Send Message
                                    </button>
                                    <button onclick="closeCallModal()" class="w-full bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.insertAdjacentHTML('beforeend', callOptions);
                });
            }
        });

        // Contact click handler
        document.querySelectorAll('.contact-item').forEach(contact => {
            contact.addEventListener('click', function() {
                const contactId = this.dataset.contactId;
                const contactName = this.dataset.contactName;
                
                console.log('Contact clicked:', contactName, 'ID:', contactId);
                
                // Update UI
                document.getElementById('chatTitle').textContent = `Chat with ${contactName}`;
                document.getElementById('chatSubtitle').textContent = 'Online';
                document.getElementById('messageInputContainer').style.display = 'block';
                
                // Enable buttons
                document.getElementById('contactInfoBtn').disabled = false;
                document.getElementById('callBtn').disabled = false;
                document.getElementById('contactInfoBtn').classList.remove('text-gray-400');
                document.getElementById('contactInfoBtn').classList.add('text-blue-600', 'hover:text-blue-700');
                document.getElementById('callBtn').classList.remove('text-gray-400');
                document.getElementById('callBtn').classList.add('text-blue-600', 'hover:text-blue-700');
                
                // Remove active class from all contacts
                document.querySelectorAll('.contact-item').forEach(c => c.classList.remove('bg-blue-50'));
                // Add active class to selected contact
                this.classList.add('bg-blue-50');
                
                currentContactId = contactId;
                
                // Clear messages area
                document.getElementById('messagesContainer').innerHTML = `
                    <div class="text-center text-gray-500 mt-20">
                        <h3 class="text-sm font-medium text-gray-900">Start a conversation with ${contactName}</h3>
                        <p class="mt-1 text-sm text-gray-500">Send your first message below.</p>
                    </div>
                `;
            });
        });

        // Call initiation functions (simplified for in-app messaging)
        window.initiateCall = function(type) {
            const contactName = document.getElementById('chatTitle').textContent.replace('Chat with ', '');
            
            switch(type) {
                case 'phone':
                    alert(`Initiating phone call to ${contactName}...\n\nNote: This would integrate with a phone service like Twilio.`);
                    break;
                case 'message':
                    // Focus on message input
                    const messageInput = document.getElementById('messageInput');
                    messageInput.focus();
                    messageInput.placeholder = `Type a message to ${contactName}...`;
                    break;
            }
            
            closeCallModal();
        };

        // Modal close functions
        window.closeCallModal = function() {
            const modal = document.getElementById('callModal');
            if (modal) modal.remove();
        };

        window.closeContactInfoModal = function() {
            const modal = document.getElementById('contactInfoModal');
            if (modal) modal.remove();
        };

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'callModal' || e.target.id === 'contactInfoModal') {
                e.target.remove();
            }
        });
    </script>
</body>
</html>








