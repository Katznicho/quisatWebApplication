<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Chat & Communications</h1>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">Communicate with your team members and send announcements.</p>
        </div>

        <!-- Chat Interface -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden h-[calc(100vh-200px)] min-h-[560px]">
            <div class="flex h-full flex-col md:flex-row">
                <!-- Left Sidebar - Contacts -->
                <div id="contactsPanel" class="w-full md:w-1/3 border-r border-gray-200 dark:border-gray-700 flex flex-col min-w-0">
                    <!-- Contacts Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Contacts</h2>
                        <!-- Search -->
                        <div class="mt-3 relative">
                            <input type="text" 
                                   id="contactSearch" 
                                   placeholder="Search contacts..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Contacts List -->
                    <div class="flex-1 overflow-y-auto p-4">
                    <div id="contactsList" class="space-y-2">
                        @if($contacts->count() > 0)
                            @foreach($contacts as $contact)
                                <div class="contact-item flex items-center p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors" 
                                     data-contact-id="{{ $contact['id'] }}" 
                                     data-contact-name="{{ $contact['name'] }}">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                            {{ substr($contact['name'], 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $contact['name'] }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $contact['role'] }}</p>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center">
                                        @if($contact['unread_count'] > 0)
                                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                                                {{ $contact['unread_count'] }}
                                            </span>
                                        @else
                                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                                <p class="text-sm">No contacts found</p>
                            </div>
                        @endif
                    </div>
                    </div>
                </div>

                <!-- Main Chat Area -->
                <div id="chatPanel" class="hidden md:flex flex-1 flex-col min-w-0">
                    <!-- Chat Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div id="chatHeader" class="flex items-center">
                            <button id="backToContactsBtn" type="button" class="mr-2 p-2 text-gray-500 hover:text-gray-700 rounded-lg md:hidden" aria-label="Back to contacts">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <div class="flex-shrink-0">
                                <img class="h-10 w-10 rounded-full bg-gray-300" src="" alt="" id="chatAvatar">
                            </div>
                            <div class="ml-3">
                                <h3 class="text-base md:text-lg font-medium text-gray-900 dark:text-gray-100" id="chatTitle">Select a contact to start chatting</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400" id="chatSubtitle"></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button id="contactInfoBtn" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="Contact Info" disabled>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                            <button id="callBtn" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="Call Contact" disabled>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="flex-1 overflow-y-auto p-4" id="messagesContainer">
                        <div class="text-center text-gray-500 dark:text-gray-400 mt-20">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No conversation selected</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose a contact from the left to start messaging.</p>
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700" id="messageInputContainer" style="display: none;">
                        <div class="flex items-center space-x-2">
                            <input type="text" 
                                   id="messageInput" 
                                   placeholder="Type a message..." 
                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
        </div>

        <!-- Broadcasted Messages Section -->
        <div class="mt-6 bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Broadcasted Messages</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Recent announcements sent to selected recipients for this school.</p>
            <div id="broadcastsList" class="space-y-3 max-h-64 overflow-y-auto">
                <div id="broadcastsLoading" class="text-center text-gray-500 dark:text-gray-400 py-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-sm">Loading broadcasts...</p>
                </div>
                <div id="broadcastsContent" class="hidden space-y-3"></div>
                <div id="broadcastsEmpty" class="hidden text-center text-gray-500 dark:text-gray-400 py-6 text-sm">
                    No broadcasts yet. Send one below.
                </div>
            </div>
        </div>

        <!-- Broadcast view modal -->
        <div id="broadcastViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[90vh] flex flex-col">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 id="broadcastModalTitle" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Broadcast</h3>
                    <button type="button" id="broadcastModalClose" class="p-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded" aria-label="Close">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="broadcastModalCloseBtn" class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">Close</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1">
                    <p id="broadcastModalType" class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2"></p>
                    <p id="broadcastModalContent" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"></p>
                    <p id="broadcastModalMeta" class="text-xs text-gray-500 dark:text-gray-500 mt-3"></p>
                </div>
            </div>
        </div>

        <!-- Broadcast Announcement Section -->
        <div class="mt-6 bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Broadcast Announcement</h3>
            <form id="broadcastForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="broadcastTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" 
                               id="broadcastTitle" 
                               name="title" 
                               required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="broadcastType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select id="broadcastType" 
                                name="type" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="general">General</option>
                            <option value="urgent">Urgent</option>
                            <option value="info">Information</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Send to</label>
                        <div class="space-y-2">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="target_roles[]" value="staff" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="text-sm text-gray-800 dark:text-gray-200">Staff</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="target_roles[]" value="parents" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                <span class="text-sm text-gray-800 dark:text-gray-200">Parents</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="target_roles[]" value="students" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="text-sm text-gray-800 dark:text-gray-200">Students</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="broadcastContent" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                    <textarea id="broadcastContent" 
                              name="content" 
                              rows="3" 
                              required 
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        Send Broadcast
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script>
        let currentConversationId = null;
        let currentContactId = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            const contactInfoBtn = document.getElementById('contactInfoBtn');
            const callBtn = document.getElementById('callBtn');
            const contactsPanel = document.getElementById('contactsPanel');
            const chatPanel = document.getElementById('chatPanel');
            const backToContactsBtn = document.getElementById('backToContactsBtn');

            function showChatPanelOnMobile() {
                if (window.innerWidth < 768) {
                    contactsPanel?.classList.add('hidden');
                    chatPanel?.classList.remove('hidden');
                    chatPanel?.classList.add('flex');
                }
            }

            function showContactsPanelOnMobile() {
                if (window.innerWidth < 768) {
                    chatPanel?.classList.add('hidden');
                    contactsPanel?.classList.remove('hidden');
                    contactsPanel?.classList.add('flex');
                }
            }

            backToContactsBtn?.addEventListener('click', showContactsPanelOnMobile);
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    contactsPanel?.classList.remove('hidden');
                    chatPanel?.classList.remove('hidden');
                    chatPanel?.classList.add('flex');
                } else if (!currentContactId) {
                    showContactsPanelOnMobile();
                }
            });
            
            const isMobileView = window.innerWidth < 768;

            // Check if there's a last selected contact in localStorage
            const lastSelectedContact = localStorage.getItem('lastSelectedContact');
            if (!isMobileView && lastSelectedContact) {
                const contactData = JSON.parse(lastSelectedContact);
                
                // Function to try restoring the contact
                function tryRestoreContact(attempt = 1) {
                    const contactElement = document.querySelector(`[data-contact-id="${contactData.id}"]`);
                    if (contactElement) {
                        contactElement.click();
                        return true;
                    } else {
                        return false;
                    }
                }
                
                // Try immediately
                if (!tryRestoreContact(1)) {
                    // Try after 500ms
                    setTimeout(() => {
                        if (!tryRestoreContact(2)) {
                            // Try after 1 second
                            setTimeout(() => {
                                if (!tryRestoreContact(3)) {
                                    // Try after 2 seconds
                                    setTimeout(() => {
                                        if (!tryRestoreContact(4)) {
                                            // Fallback: select the first contact
                                            const firstContact = document.querySelector('.contact-item');
                                            if (firstContact) {
                                                firstContact.click();
                                            }
                                        }
                                    }, 2000);
                                }
                            }, 1000);
                        }
                    }, 500);
                }
            } else if (!isMobileView) {
                // No localStorage data, select the first contact after a delay
                setTimeout(() => {
                    const firstContact = document.querySelector('.contact-item');
                    if (firstContact) {
                        firstContact.click();
                    }
                }, 1000);
            }
            
            // Add contact info functionality
            if (contactInfoBtn) {
                contactInfoBtn.addEventListener('click', function() {
                    if (!currentContactId) {
                        alert('Please select a contact first');
                        return;
                    }
                    
                    const contactName = document.getElementById('chatTitle').textContent.replace('Chat with ', '');
                    
                    // Show contact info modal
                    const contactInfoModal = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="contactInfoModal">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Contact Information</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                            ${contactName.charAt(0)}
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">${contactName}</h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Online</p>
                                        </div>
                                    </div>
                                    <div class="border-t pt-3">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Role: Staff Member</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Department: General</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Last seen: Just now</p>
                                    </div>
                                    <button onclick="closeContactInfoModal()" class="w-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
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
                    if (!currentContactId) {
                        alert('Please select a contact first');
                        return;
                    }
                    
                    const contactName = document.getElementById('chatTitle').textContent.replace('Chat with ', '');
                    
                    // Show simple call options
                    const callOptions = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="callModal">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Call ${contactName}</h3>
                                <div class="space-y-3">
                                    <button onclick="initiateCall('phone')" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                                        📞 Call Phone
                                    </button>
                                    <button onclick="initiateCall('message')" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                                        💬 Send Message
                                    </button>
                                    <button onclick="closeCallModal()" class="w-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.insertAdjacentHTML('beforeend', callOptions);
                });
            }
            // Contact search functionality
            const contactSearch = document.getElementById('contactSearch');
            if (contactSearch) {
                contactSearch.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const contacts = document.querySelectorAll('.contact-item');
                    
                    contacts.forEach(contact => {
                        const name = contact.dataset.contactName.toLowerCase();
                        if (name.includes(searchTerm)) {
                            contact.style.display = 'block';
                        } else {
                            contact.style.display = 'none';
                        }
                    });
                });
            }

            // Contact click handler - using event delegation
            function handleContactClick(e) {
                // Find the closest contact-item element
                const contactItem = e.target.closest('.contact-item');
                
                if (!contactItem) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                const contactId = contactItem.getAttribute('data-contact-id');
                const contactName = contactItem.getAttribute('data-contact-name');
                
                if (!contactId || !contactName) {
                    console.error('Contact ID or Name missing', { 
                        contactId, 
                        contactName,
                        dataset: contactItem.dataset,
                        attributes: {
                            id: contactItem.getAttribute('data-contact-id'),
                            name: contactItem.getAttribute('data-contact-name')
                        }
                    });
                    return;
                }
                
                console.log('Contact clicked:', contactName, 'ID:', contactId);
                
                // Update UI
                const chatTitle = document.getElementById('chatTitle');
                const chatSubtitle = document.getElementById('chatSubtitle');
                const messageInputContainer = document.getElementById('messageInputContainer');

                showChatPanelOnMobile();
                
                if (chatTitle) {
                    chatTitle.textContent = `Chat with ${contactName}`;
                }
                if (chatSubtitle) {
                    chatSubtitle.textContent = 'Online';
                }
                if (messageInputContainer) {
                    messageInputContainer.style.display = 'block';
                }
                
                // Enable buttons
                const contactInfoBtn = document.getElementById('contactInfoBtn');
                const callBtn = document.getElementById('callBtn');
                if (contactInfoBtn) {
                    contactInfoBtn.disabled = false;
                    contactInfoBtn.classList.remove('text-gray-400');
                    contactInfoBtn.classList.add('text-blue-600', 'hover:text-blue-700');
                }
                if (callBtn) {
                    callBtn.disabled = false;
                    callBtn.classList.remove('text-gray-400');
                    callBtn.classList.add('text-blue-600', 'hover:text-blue-700');
                }
                
                // Remove active class from all contacts
                document.querySelectorAll('.contact-item').forEach(c => {
                    c.classList.remove('bg-blue-50', 'dark:bg-blue-900');
                });
                // Add active class to selected contact
                contactItem.classList.add('bg-blue-50', 'dark:bg-blue-900');
                
                currentContactId = contactId;
                
                // Save selected contact to localStorage
                localStorage.setItem('lastSelectedContact', JSON.stringify({
                    id: contactId,
                    name: contactName
                }));
                
                // Load existing conversation and messages
                loadConversation(contactId, contactName);
            }
            
            // Setup contact click handler with retry logic
            function setupContactClickHandler() {
                const contactsList = document.getElementById('contactsList');
                
                if (!contactsList) {
                    console.warn('Contacts list container not found, retrying...');
                    setTimeout(setupContactClickHandler, 100);
                    return;
                }
                
                // Use event delegation on the contacts list
                contactsList.addEventListener('click', handleContactClick, true);
                console.log('Contact click handler attached successfully');
            }
            
            // Setup contact click handler
            setupContactClickHandler();
        });

        // Load conversation and messages
        function loadConversation(contactId, contactName) {
            
            // Show loading state
            document.getElementById('messagesContainer').innerHTML = `
                <div class="text-center text-gray-500 dark:text-gray-400 mt-20">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-sm">Loading conversation...</p>
                </div>
            `;

            // Check if conversation exists by trying to get conversations
            fetch('/chat/conversations', {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => {
                    return response.json();
                })
                .then(conversations => {
                    // Check if conversations is an array
                    if (!Array.isArray(conversations)) {
                        throw new Error('Invalid response format');
                    }
                    
                    // Find conversation with this contact
                    const conversation = conversations.find(conv => {
                        return conv.participants.some(p => p.id == contactId);
                    });

                    if (conversation) {
                        // Load messages for existing conversation
                        currentConversationId = conversation.id;
                        loadMessages(conversation.id);
                    } else {
                        // No existing conversation, show start message
                        currentConversationId = null;
                        document.getElementById('messagesContainer').innerHTML = `
                            <div class="text-center text-gray-500 dark:text-gray-400 mt-20">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Start a conversation with ${contactName}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send your first message below.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('messagesContainer').innerHTML = `
                        <div class="text-center text-gray-500 dark:text-gray-400 mt-20">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Start a conversation with ${contactName}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send your first message below.</p>
                        </div>
                    `;
                });
        }

        // Load messages for a conversation
        function loadMessages(conversationId) {
            
            fetch(`/chat/conversations/${conversationId}/messages`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(messages => {
                    const messagesContainer = document.getElementById('messagesContainer');
                    
                    // Check if messages is an array
                    if (!Array.isArray(messages)) {
                        messagesContainer.innerHTML = `
                            <div class="text-center text-red-500 dark:text-red-400 mt-20">
                                <h3 class="text-sm font-medium">Error loading messages</h3>
                                <p class="mt-1 text-sm">Please try again or contact support.</p>
                            </div>
                        `;
                        return;
                    }
                    
                    if (messages.length === 0) {
                        messagesContainer.innerHTML = `
                            <div class="text-center text-gray-500 dark:text-gray-400 mt-20">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">No messages yet</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start the conversation below.</p>
                            </div>
                        `;
                        return;
                    }

                    
                    // Render messages
                    let messagesHtml = '';
                    const currentUserId = {!! json_encode(auth()->id()) !!};
                    messages.forEach(message => {
                        const isOwnMessage = message.sender_id == currentUserId;
                        const messageClass = isOwnMessage ? 'justify-end' : 'justify-start';
                        const bubbleClass = isOwnMessage ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100';
                        
                        messagesHtml += `
                            <div class="flex ${messageClass} mb-4">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${bubbleClass}">
                                    <p class="text-sm">${message.content}</p>
                                    <p class="text-xs ${isOwnMessage ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400'} mt-1">
                                        ${new Date(message.created_at).toLocaleTimeString()}
                                    </p>
                                </div>
                            </div>
                        `;
                    });

                    messagesContainer.innerHTML = messagesHtml;
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
                    // Mark messages as read
                    markMessagesAsRead(conversationId);
                })
                .catch(error => {
                    
                    // If it's a 404 error, treat it as if no conversation exists
                    if (error.message.includes('404')) {
                        currentConversationId = null;
                        document.getElementById('messagesContainer').innerHTML = `
                            <div class="text-center text-gray-500 dark:text-gray-400 mt-20">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Start a conversation</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send your first message below.</p>
                            </div>
                        `;
                    } else {
                        document.getElementById('messagesContainer').innerHTML = `
                            <div class="text-center text-gray-500 dark:text-gray-400 mt-20">
                                <p class="text-sm text-red-500">Error loading messages</p>
                            </div>
                        `;
                    }
                });
        }

        // Mark messages as read
        function markMessagesAsRead(conversationId) {
            fetch(`/chat/conversations/${conversationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                // Update unread count in UI
                updateUnreadCounts();
            })
            .catch(error => {
            });
        }

        // Update unread counts in the contacts list
        function updateUnreadCounts() {
            // This would typically refresh the contacts list or update specific counts
            // For now, we'll just log that it should be updated
        }

        // Send message functionality
        document.getElementById('sendMessageBtn').addEventListener('click', function() {
            const messageInput = document.getElementById('messageInput');
            const content = messageInput.value.trim();
            
            if (content && currentContactId) {
                // Show loading state
                const sendBtn = document.getElementById('sendMessageBtn');
                const originalText = sendBtn.innerHTML;
                sendBtn.innerHTML = '<svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                sendBtn.disabled = true;
                
                // Send message to backend
                const url = currentConversationId 
                    ? `/chat/conversations/${currentConversationId}/messages`
                    : '/chat/conversations';
                
                const requestBody = currentConversationId 
                    ? { content: content }
                    : { participant_ids: [currentContactId], message_content: content };


                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(requestBody)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    
                    // Add message to UI
                    const messagesContainer = document.getElementById('messagesContainer');
                    const messageHtml = `
                        <div class="flex justify-end mb-4">
                            <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-blue-600 text-white">
                                <p class="text-sm">${content}</p>
                                <p class="text-xs text-blue-100 mt-1">${new Date().toLocaleTimeString()}</p>
                            </div>
                        </div>
                    `;
                    
                    if (messagesContainer.innerHTML.includes('Start a conversation') || messagesContainer.innerHTML.includes('No messages yet')) {
                        messagesContainer.innerHTML = messageHtml;
                    } else {
                        messagesContainer.innerHTML += messageHtml;
                    }
                    
                    messageInput.value = '';
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
                    // Update current conversation ID
                    if (data.conversation) {
                        currentConversationId = data.conversation.id;
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', {
                        message: error.message,
                        stack: error.stack,
                        url: url,
                        requestBody: requestBody
                    });
                    
                    // If it's a 404 error, the conversation doesn't exist or user doesn't have access
                    if (error.message.includes('404')) {
                        // Reset conversation ID and try to create a new conversation
                        currentConversationId = null;
                        
                        // Try to send the message again with a new conversation
                        const newUrl = '/chat/conversations';
                        const newRequestBody = {
                            participant_ids: [currentContactId],
                            type: 'direct',
                            message_content: messageInput.value
                        };
                        
                        fetch(newUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(newRequestBody)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Clear the input
                            messageInput.value = '';
                            // Update current conversation ID
                            if (data.conversation) {
                                currentConversationId = data.conversation.id;
                            }
                            // Reload the conversation to show the new message
                            if (currentContactId) {
                                loadConversation(currentContactId, document.getElementById('chatTitle').textContent.replace('Chat with ', ''));
                            }
                        })
                        .catch(retryError => {
                            alert('Failed to create new conversation: ' + retryError.message);
                        });
                    } else {
                        alert('Failed to send message: ' + error.message);
                    }
                })
                .finally(() => {
                    // Restore button state
                    sendBtn.innerHTML = originalText;
                    sendBtn.disabled = false;
                });
            }
        });

        // Enter key to send message
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('sendMessageBtn').click();
            }
        });

        // Load broadcasted messages
        function loadBroadcasts() {
            const loadingEl = document.getElementById('broadcastsLoading');
            const contentEl = document.getElementById('broadcastsContent');
            const emptyEl = document.getElementById('broadcastsEmpty');
            if (!loadingEl || !contentEl || !emptyEl) return;

            loadingEl.classList.remove('hidden');
            contentEl.classList.add('hidden');
            contentEl.innerHTML = '';
            emptyEl.classList.add('hidden');

            fetch('/chat/broadcasts', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => response.json())
                .then(broadcasts => {
                    loadingEl.classList.add('hidden');
                    if (!Array.isArray(broadcasts) || broadcasts.length === 0) {
                        emptyEl.classList.remove('hidden');
                        return;
                    }
                    const typeBadge = (type) => {
                        const classes = type === 'urgent' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : (type === 'info' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300');
                        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' + classes + '">' + (type || 'general') + '</span>';
                    };
                    broadcasts.forEach(b => {
                        const sentAt = b.sent_at ? new Date(b.sent_at).toLocaleString() : '';
                        const bid = escapeHtml(String(b.id));
                        const bTitle = escapeHtml(b.title);
                        const bContent = escapeHtml(b.content);
                        const bType = escapeHtml(b.type || 'general');
                        const bSender = escapeHtml(b.sender_name || 'School');
                        contentEl.innerHTML += `
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 broadcast-card" data-broadcast-id="${bid}" data-broadcast-title="${bTitle.replace(/"/g, '&quot;')}" data-broadcast-content="${bContent.replace(/"/g, '&quot;').replace(/\n/g, '&#10;')}" data-broadcast-type="${bType}" data-broadcast-sender="${bSender.replace(/"/g, '&quot;')}" data-broadcast-sent="${escapeHtml(sentAt)}">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 text-sm cursor-pointer hover:underline" data-view-broadcast>${bTitle}</h4>
                                    ${typeBadge(b.type)}
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2 cursor-pointer" data-view-broadcast>${bContent}</p>
                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-500">${bSender} · ${sentAt}</p>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="text-xs px-2 py-1 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded" data-view-broadcast>View</button>
                                        <button type="button" class="text-xs px-2 py-1 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded" data-delete-broadcast>Delete</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    contentEl.classList.remove('hidden');
                    // View broadcast: open modal with data from card
                    contentEl.querySelectorAll('[data-view-broadcast]').forEach(el => {
                        el.addEventListener('click', function() {
                            const card = this.closest('.broadcast-card');
                            if (!card) return;
                            document.getElementById('broadcastModalTitle').textContent = card.dataset.broadcastTitle || 'Broadcast';
                            document.getElementById('broadcastModalType').textContent = (card.dataset.broadcastType || 'general').charAt(0).toUpperCase() + (card.dataset.broadcastType || 'general').slice(1);
                            document.getElementById('broadcastModalContent').textContent = (card.dataset.broadcastContent || '').replace(/&#10;/g, '\n');
                            document.getElementById('broadcastModalMeta').textContent = (card.dataset.broadcastSender || '') + ' · ' + (card.dataset.broadcastSent || '');
                            document.getElementById('broadcastViewModal').classList.remove('hidden');
                        });
                    });
                    contentEl.querySelectorAll('[data-delete-broadcast]').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const card = this.closest('.broadcast-card');
                            if (!card || !confirm('Delete this broadcast? This cannot be undone.')) return;
                            const id = card.dataset.broadcastId;
                            fetch('/chat/broadcasts/' + id, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        card.remove();
                                        if (contentEl.querySelectorAll('.broadcast-card').length === 0) {
                                            contentEl.classList.add('hidden');
                                            document.getElementById('broadcastsEmpty').classList.remove('hidden');
                                        }
                                    } else {
                                        alert(data.message || 'Could not delete broadcast.');
                                    }
                                })
                                .catch(() => alert('Could not delete broadcast.'));
                        });
                    });
                    contentEl.classList.remove('hidden');
                })
                .catch(() => {
                    loadingEl.classList.add('hidden');
                    emptyEl.classList.remove('hidden');
                    emptyEl.textContent = 'Unable to load broadcasts.';
                });
        }
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        loadBroadcasts();

        // Broadcast view modal close
        function closeBroadcastModal() {
            document.getElementById('broadcastViewModal').classList.add('hidden');
        }
        document.getElementById('broadcastModalClose').addEventListener('click', closeBroadcastModal);
        document.getElementById('broadcastModalCloseBtn').addEventListener('click', closeBroadcastModal);
        document.getElementById('broadcastViewModal').addEventListener('click', function(e) {
            if (e.target === this) closeBroadcastModal();
        });

        // Broadcast form submission
        document.getElementById('broadcastForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span> Sending...';

            const checkedTargets = Array.from(form.querySelectorAll('input[name="target_roles[]"]:checked'))
                .map(el => el.value);

            const data = {
                title: document.getElementById('broadcastTitle').value.trim(),
                content: document.getElementById('broadcastContent').value.trim(),
                type: document.getElementById('broadcastType').value,
                target_roles: checkedTargets
            };

            fetch('/chat/broadcast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
                .then(response => {
                    if (!response.ok) return response.json().then(j => { throw new Error(j.message || 'Failed to send broadcast'); });
                    return response.json();
                })
                .then(result => {
                    const parentsCount = result.delivered_to_parents != null ? result.delivered_to_parents : 0;
                    const staffCount = result.delivered_to_staff != null ? result.delivered_to_staff : 0;
                    const studentsCount = result.delivered_to_students != null ? result.delivered_to_students : 0;

                    let msg = 'Broadcast sent successfully!';
                    const parts = [];
                    if (parentsCount > 0) parts.push(parentsCount + ' parent(s)');
                    if (staffCount > 0) parts.push(staffCount + ' staff');
                    if (studentsCount > 0) parts.push(studentsCount + ' student(s)');
                    if (parts.length > 0) msg += ' Delivered to ' + parts.join(', ') + '.';
                    alert(msg);
                    form.reset();
                    loadBroadcasts();
                })
                .catch(err => {
                    alert(err.message || 'Failed to send broadcast. Please try again.');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });

        // Phone Call functionality - moved to DOMContentLoaded

        // Video Call functionality - moved to DOMContentLoaded

        // Call initiation functions (simplified for in-app messaging)
        window.initiateCall = function(type) {
            const contactName = document.getElementById('chatTitle').textContent.replace('Chat with ', '');
            
            switch(type) {
                case 'phone':
                    // For phone calls, you could integrate with Twilio or similar service
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

</x-app-layout>
