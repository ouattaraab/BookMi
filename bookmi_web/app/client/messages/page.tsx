'use client';

import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { messageApi } from '@/lib/api/endpoints';
import { useAuthStore } from '@/lib/store/auth';
import { Send, MessageSquare } from 'lucide-react';

type Conversation = {
  id: number;
  unread_count: number;
  last_message?: { content: string; created_at: string };
  participant?: { id: number; first_name: string; last_name: string; stage_name?: string };
};

type Message = {
  id: number;
  content: string;
  sender_id: number;
  created_at: string;
};

export default function ClientMessagesPage() {
  const user = useAuthStore((s) => s.user);
  const qc = useQueryClient();
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [newMsg, setNewMsg] = useState('');
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const { data: convData, isLoading: convLoading } = useQuery({
    queryKey: ['conversations'],
    queryFn: () => messageApi.listConversations(),
    refetchInterval: 15_000,
  });
  const conversations: Conversation[] = convData?.data?.data ?? [];

  const { data: msgData } = useQuery({
    queryKey: ['messages', selectedId],
    queryFn: () => messageApi.getMessages(selectedId!),
    enabled: !!selectedId,
    refetchInterval: 8_000,
  });
  const messages: Message[] = msgData?.data?.data ?? [];

  const sendMutation = useMutation({
    mutationFn: () => messageApi.sendMessage(selectedId!, newMsg.trim()),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['messages', selectedId] });
      qc.invalidateQueries({ queryKey: ['conversations'] });
      setNewMsg('');
    },
  });

  // Mark conversation as read when opened
  useEffect(() => {
    if (selectedId) {
      messageApi.markRead(selectedId).catch(() => {});
      qc.invalidateQueries({ queryKey: ['conversations'] });
    }
  }, [selectedId, qc]);

  // Scroll to bottom on new messages
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages.length]);

  const selectedConv = conversations.find((c) => c.id === selectedId);
  const participantName = selectedConv?.participant
    ? selectedConv.participant.stage_name ??
      `${selectedConv.participant.first_name} ${selectedConv.participant.last_name}`
    : '...';

  const handleSend = () => {
    if (!newMsg.trim() || !selectedId) return;
    sendMutation.mutate();
  };

  return (
    <div className="max-w-5xl mx-auto" style={{ height: 'calc(100vh - 140px)' }}>
      <div className="mb-4">
        <h1 className="text-2xl font-extrabold text-gray-900">Messages</h1>
        <p className="text-gray-500 text-sm mt-1">Vos conversations avec les artistes</p>
      </div>

      <div
        className="flex rounded-2xl overflow-hidden"
        style={{
          height: 'calc(100% - 70px)',
          background: 'rgba(255,255,255,0.82)',
          backdropFilter: 'blur(12px)',
          WebkitBackdropFilter: 'blur(12px)',
          border: '1px solid rgba(255,255,255,0.9)',
          boxShadow: '0 4px 20px rgba(0,0,0,0.05)',
        }}
      >
        {/* Conversation list */}
        <div className="w-72 border-r border-gray-100 flex flex-col flex-shrink-0 hidden md:flex">
          <div className="px-4 py-4 border-b border-gray-100">
            <p className="font-bold text-gray-900 text-sm">Conversations</p>
          </div>
          <div className="flex-1 overflow-y-auto">
            {convLoading ? (
              <div className="p-4 space-y-3">
                {[...Array(3)].map((_, i) => <div key={i} className="h-14 rounded-xl bg-gray-100 animate-pulse" />)}
              </div>
            ) : conversations.length === 0 ? (
              <div className="text-center py-12 px-4">
                <MessageSquare size={28} className="text-gray-200 mx-auto mb-2" />
                <p className="text-xs text-gray-400">Aucune conversation</p>
              </div>
            ) : (
              conversations.map((conv) => {
                const fullName = `${conv.participant?.first_name ?? ''} ${conv.participant?.last_name ?? ''}`.trim();
                const name = conv.participant?.stage_name ?? (fullName || '...');
                const initials = name.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase();
                const isActive = selectedId === conv.id;

                return (
                  <button
                    key={conv.id}
                    onClick={() => setSelectedId(conv.id)}
                    className="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors"
                    style={isActive ? { background: 'rgba(33,150,243,0.06)' } : {}}
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                        style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
                      >
                        {initials || '?'}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                          <p className="text-sm font-semibold text-gray-900 truncate">{name}</p>
                          {conv.unread_count > 0 && (
                            <span className="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0" style={{ background: '#FF6B35' }}>
                              {conv.unread_count}
                            </span>
                          )}
                        </div>
                        {conv.last_message && (
                          <p className="text-xs text-gray-400 truncate mt-0.5">{conv.last_message.content}</p>
                        )}
                      </div>
                    </div>
                  </button>
                );
              })
            )}
          </div>
        </div>

        {/* Message area */}
        <div className="flex-1 flex flex-col min-w-0">
          {!selectedId ? (
            <div className="flex-1 flex items-center justify-center text-center px-4">
              <div>
                <MessageSquare size={40} className="text-gray-200 mx-auto mb-3" />
                <p className="text-gray-400 font-semibold">Sélectionnez une conversation</p>
                <p className="text-gray-300 text-sm mt-1">pour lire et envoyer des messages</p>
              </div>
            </div>
          ) : (
            <>
              {/* Header */}
              <div className="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                <div
                  className="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                  style={{ background: 'linear-gradient(135deg, #1A2744, #2196F3)' }}
                >
                  {participantName.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase() || '?'}
                </div>
                <p className="font-bold text-gray-900 text-sm">{participantName}</p>
              </div>

              {/* Messages */}
              <div className="flex-1 overflow-y-auto p-5 space-y-3">
                {messages.map((msg) => {
                  const isMine = msg.sender_id === user?.id;
                  return (
                    <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                      <div
                        className="max-w-xs md:max-w-sm px-4 py-2.5 rounded-2xl text-sm leading-relaxed"
                        style={
                          isMine
                            ? { background: 'linear-gradient(135deg, #1A2744, #2196F3)', color: 'white', borderBottomRightRadius: 4 }
                            : { background: '#F1F5F9', color: '#1A2744', borderBottomLeftRadius: 4 }
                        }
                      >
                        {msg.content}
                        <p className={`text-[10px] mt-1 ${isMine ? 'text-blue-200' : 'text-gray-400'}`}>
                          {new Date(msg.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                        </p>
                      </div>
                    </div>
                  );
                })}
                <div ref={messagesEndRef} />
              </div>

              {/* Input */}
              <div className="px-4 py-4 border-t border-gray-100">
                <div className="flex gap-3 items-end">
                  <textarea
                    value={newMsg}
                    onChange={(e) => setNewMsg(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); }
                    }}
                    placeholder="Écrivez votre message..."
                    className="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm outline-none focus:border-blue-400 resize-none"
                    rows={1}
                    style={{ maxHeight: 100 }}
                  />
                  <button
                    onClick={handleSend}
                    disabled={!newMsg.trim() || sendMutation.isPending}
                    className="p-3 rounded-xl text-white disabled:opacity-50 flex-shrink-0 transition-opacity"
                    style={{ background: 'linear-gradient(135deg, #FF6B35, #C85A20)' }}
                  >
                    <Send size={16} />
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
